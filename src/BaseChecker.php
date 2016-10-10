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
     * @var bool
     */
    protected $localCache = false;

    /**
     * BaseChecker constructor.
     */
    public function __construct()
    {
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
            $client = new ApiClient();
            $out = $client->getChecksums($type, $slug, $version);
        }
        return $out;
    }

    /**
     * Calculate changes between local and orignial
     *
     * @param $original
     * @param $local
     * @return array
     */
    public function getChangeSet($original, $local)
    {
        $changeSet = array();
        foreach ($original->checksums as $key => $originalFile) {
            if (isset($local->checksums[$key])) {
                if ($originalFile->hash != $local->checksums[$key]->hash) {
                    $change = $originalFile;
                    $change->status = 'MODIFIED';
                    $changeSet[$key] = $change;
                }
            } else {
                $change = $originalFile;
                $change->status = 'DELETED';
                $changeSet[$key] = $change;
            }
        }

        foreach ($local->checksums as $key => $localFile) {
            if (!isset($original->checksums[$key])) {
                $change = $localFile;
                $change->status = 'ADDED';
                $changeSet[$key] = $change;
            }
        }

        return $changeSet;
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