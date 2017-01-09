<?php
namespace WPChecksum;

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
     */
    public function __construct($apiClient, $localCache = false)
    {
        $this->apiClient = $apiClient;
        $this->localCache = $localCache;
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

            $url = sprintf($localTemplate, $slug, $version);
            $ret = $this->downloadZip($url);
            if ($ret) {
                $path = $ret . "/$slug";
                $out = $this->getLocalChecksums($path);
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
     * @param array $original
     * @param array $local
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
     * @param $url
     * @return null|string
     */
    private function downloadZip($url)
    {
        $response = wp_remote_get($url);
        if ($response['response']['code'] != 200) {
            return null;
        }

        $fileName = wp_tempnam();
        file_put_contents($fileName, $response['body']);

        $folderName = $fileName . '.extracted';
        @mkdir($folderName, 0777, true);
        $zip = new \ZipArchive;
        $zip->open($fileName);
        $zip->extractTo($folderName);
        $zip->close();
        unlink($fileName);

        return $folderName;

    }
}