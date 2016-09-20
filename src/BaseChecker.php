<?php
namespace WPChecksum;

class BaseChecker
{
    const PLUGIN_URL_TEMPLATE = "https://downloads.wordpress.org/plugin/%s.%s.zip";
    const THEME_URL_TEMPLATE  = "https://downloads.wordpress.org/theme/%s.%s.zip";

    const API_PLUGIN_URL_TEMPLATE = "http://api.wpessentials.io/v1/checksum/plugin/%s/%s";
    const API_THEME_URL_TEMPLATE  = "http://api.wpessentials.io/v1/checksum/theme/%s/%s";

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

    public function getLocalChecksums($path, $base = '')
    {
        $fileName = wp_tempnam();
        $fileHandle = fopen($fileName, 'w');
        if (!$base) {
            $base = $this->basePath;
        }
        $this->recScandir($path, $fileHandle, $path);
        fclose($fileHandle);

        $out =  $this->flatToJson(file_get_contents($fileName));
        unlink($fileName);
        return $out;
    }

    public function getOriginalChecksums($type, $slug, $version, $locale = '')
    {
        $out = null;
        switch ($type) {
            case 'plugin':
                $localTemplate = self::PLUGIN_URL_TEMPLATE;
                $apiTemplate = self::API_PLUGIN_URL_TEMPLATE;
                break;
            case 'theme':
                $localTemplate = self::THEME_URL_TEMPLATE;
                $apiTemplate = self::API_THEME_URL_TEMPLATE;
                break;
        }

        if ($this->localCache) {
            $url = sprintf($localTemplate, $slug, $version);
            $ret = $this->downloadZip($url);
            if ($ret) {
                $path = $ret . "/$slug";
                $out = $this->getLocalChecksums($path, $ret);
            }
        } else {
            $url = sprintf($apiTemplate, $slug, $version);
            $out = wp_remote_get($url);
            if (is_wp_error($out)) {
                return null;
            }
            if ($out['response']['code'] != 200) {
                $out = null;
            } else {
                $out = json_decode($out['body']);
                if (isset($out->checksums)) {
                    $out->checksums = (array)$out->checksums;
                }
            }
        }

        return $out;
    }

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

    private function flatToJson($flat)
    {
        $out = new \stdClass();
        $checksums = array();
        $rows = explode("\n", $flat);
        foreach($rows as $row) {
            if (trim($row) == '') {
                continue;
            }

            $cols = explode("\t", trim($row));
            // Skip directories
            if ($cols[4] == '1' || count($cols) == 1) {
                continue;
            }

            $obj = new \stdClass();
            $obj->hash = $cols[3];
            $checksums[$cols[0]] = $obj;
        }

        $out->checksums = $checksums;
        return $out;
    }

    private function recScandir($dir, $f, $base)
    {
        $dir = rtrim($dir, '/');
        $root = scandir($dir);
        foreach ($root as $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }
            /*if ($this->fnInArray("$dir/$value", $this->ignore)) {
                continue;
            }*/
            if (is_file("$dir/$value")) {
                $this->fileInfo2File($f, "$dir/$value", $base);
                continue;
            }
            $this->fileInfo2File($f, "$dir/$value", $base);
            $this->recScandir("$dir/$value", $f, $base);
        }
    }

    private function fileInfo2File($f, $file, $base)
    {
        $stat = stat($file);
        $sum = md5_file($file);
        $base = rtrim($base, '/') . '/';
        $relfile = substr($file, strlen($base));
        $row =  array(
            $relfile,
            is_dir($file) ? 0 : $stat['mtime'],
            is_dir($file) ? 0 : $stat['size'],
            is_dir($file) ? 0 : $sum,
            (int) is_dir($file),
            (int) is_file($file),
            (int) is_link($file),
        );
        fwrite($f, join("\t", $row) . "\n");
    }

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

    private function fnInArray($needle, $haystack)
    {
        # this function allows wildcards in the array to be searched
        $needle = substr($needle, strlen($this->basePath));#
        foreach ($haystack as $value) {
            if (true === fnmatch($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}