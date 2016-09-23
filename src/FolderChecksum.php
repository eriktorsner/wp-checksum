<?php
namespace WPChecksum;

class FolderChecksum
{
    private $path;

    private $output;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function scan()
    {
        $fileName = wp_tempnam();
        $fileHandle = fopen($fileName, 'w');

        $this->recScandir($this->path, $fileHandle, $this->path);
        fclose($fileHandle);

        $this->output =  $this->flatToJson(file_get_contents($fileName));
        unlink($fileName);
        return $this->output;
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

        $out->status = 200;
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