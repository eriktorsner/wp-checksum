<?php
namespace WPChecksum;

/**
 * Class BaseChecker
 * @package WPChecksum
 */
class BaseChecker
{
    const PLUGIN_URL_TEMPLATE = "https://downloads.wordpress.org/plugin/%s.%s.zip";
    const THEME_URL_TEMPLATE  = "https://downloads.wordpress.org/theme/%s.%s.zip";

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * Use a local file cache of the backend API
     * @var bool
     */
    protected $localCache = false;

    /**
     * Cache dir when using local cache. Defaults to /tmp
     *
     * @var string|bool
     */
    protected $localCacheDir = null;

    /**
     * @var array
     */
    protected $softIssues = array();

    /**
     * Client for requests to api.wpessentials.io
     * @var object
     */
    private $apiClient;

    /**
     * BaseChecker constructor.
     *
     * @param object $apiClient
     * @param bool   $localCache
     * @param string $localCacheDir
     */
    public function __construct($apiClient, $localCache = false, $localCacheDir = null)
    {
        $this->apiClient = $apiClient;
        $this->localCache = $localCache;

        $this->localCacheDir = $localCacheDir;
        if (is_null($localCacheDir)) {
            $this->localCacheDir = sys_get_temp_dir();
        }
        $this->localCacheDir = rtrim($this->localCacheDir, '/');
    }

    /**
     * Read local checksums
     *
     * @param $path
     * @return \stdClass
     */
    public function getLocalChecksums($path)
    {
        $checkSummer = new FolderChecksum($path);
        $out = $checkSummer->scan();

        return $out;
    }

    /**
     * Read original checksums from the API
     *
     * @param string $type
     * @param string $slug
     * @param string $version
     *
     * @return array|mixed|null|object|\stdClass|\WP_Error
     */
    public function getOriginalChecksums($type, $slug, $version)
    {
        $out = null;

        if ($this->localCache) {
            switch ($type) {
                case 'plugin':
                    $localTemplate = self::PLUGIN_URL_TEMPLATE;
                    break;
                case 'theme':
                    $localTemplate = self::THEME_URL_TEMPLATE;
                    break;
            }

            $zipFile = $this->cachedFileName($type, $slug, $version);
            if (!file_exists($zipFile)) {
                $url = sprintf($localTemplate, $slug, $version);
                $repoInfo = new WPRepository($slug, $type);
                if (!is_null($repoInfo->slug) && $repoInfo->version == $version) {
                    $url = $repoInfo->download_link;
                }
                $this->downloadZip($url, $zipFile);
            }

            if (file_exists($zipFile)) {
                $ret = $this->extractZip($zipFile);
                $path = $ret . "/$slug";

                $out = $this->getLocalChecksums($path);
                $this->rrmdir($ret);

            }
        } else {
            $out = $this->apiClient->getChecksums($type, $slug, $version);
        }
        return $out;
    }

    /**
     * Compare the original set of files/checksums to the local
     * set.
     *
     * @param \stdClass $original
     * @param \stdClass $local
     *
     * @return array A set of changed files
     */
    public function getChangeSet($original, $local)
    {
        $changeSet = array();
        foreach ($original->checksums as $key => $originalFile) {
            if (isset($local->checksums[$key])) {
                if ($originalFile->hash != $local->checksums[$key]->hash) {
                    $change = $originalFile;
                    $change->status = 'MODIFIED';
	                $change->isSoft = $this->isSoftChange($key, $change->status);
                    $changeSet[$key] = $change;
                }
            } else {
                $change = $originalFile;
                $change->status = 'DELETED';
	            $change->isSoft = $this->isSoftChange($key, $change->status);
                $changeSet[$key] = $change;
            }
        }

        foreach ($local->checksums as $key => $localFile) {
            if (!isset($original->checksums[$key])) {
                $change = $localFile;
                $change->status = 'ADDED';
	            $change->isSoft = $this->isSoftChange($key, $change->status);
                $changeSet[$key] = $change;
            }
        }

        return $changeSet;
    }

    /**
     * Check if a change can be classified as "soft"
     *
     * @param string $changedFile
     * @param string $status
     *
     * @return boolean
     */
    private function isSoftChange($changedFile, $status)
    {
	    foreach ($this->softIssues as $pattern => $allowed) {
		    if (fnmatch($pattern, $changedFile)) {
			    return true;
		    }
	    }

	    return false;
    }

    /**
     * Download and unpack zip file
     *
     * @param string $url
     * @param string $target
     *
     * @return boolean
     */
    private function downloadZip($url, $target)
    {
        $response = wp_remote_get($url, array('timeout' => 15));
        if ($response['response']['code'] != 200) {
            return false;
        }
        file_put_contents($target, $response['body']);
        return true;
    }

    /**
     * @param string $zipFile
     *
     * @return string
     */
    private function extractZip($zipFile)
    {
        $extractFolder = sys_get_temp_dir() . '/chksm_' . md5(microtime(true)) . '.extracted';
        @mkdir($extractFolder, 0777, true);
        $zip = new \ZipArchive;
        $zip->open($zipFile);
        $zip->extractTo($extractFolder);
        $zip->close();

        return $extractFolder;
    }

    /**
     *
     * @param string $type
     * @param string $slug
     * @param string $version
     *
     * @return string|boolean Path to file if found or false
     */
    private function cachedFileName($type, $slug, $version)
    {
        $fileName = $this->localCacheDir . '/' . md5(join('.', array($type, $slug, $version)));
        return $fileName;
    }

    /**
     * Recursive remove dir
     *
     * @param string $dir
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object == "." || $object == "..") {
                    continue;
                }
                if (is_dir($dir."/".$object)) {
                    $this->rrmdir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
            rmdir($dir);
        }
    }
}