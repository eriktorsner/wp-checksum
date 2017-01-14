<?php
namespace WPChecksum;

class FolderChecksum
{
    /**
     * Recurse into sub folders?
     *
     * @var bool
     */
    public $recursive = true;

    /**
     * Calculate MD5 hashes or not
     *
     * @var bool
     */
    public $calcHash = true;

    /**
     * Return information about folders?
     *
     * @var bool
     */
    public $includeFolderInfo = false;

    /**
     * Path to scan
     *
     * @var string
     */
    private $path;

    /**
     * Alternate base folder
     *
     * @var string
     */
    private $basePath;

    /**
     * Patterns to ignore from scan. Evaluated with fnmatch()
     *
     * @var array
     */
    private $ignore = array();

    /**
     * FolderChecksum constructor.
     *
     * @param string $path target folder
     * @param string $base alternate base folder
     */
    public function __construct($path, $base = '')
    {
        $this->ignore = array();
        $this->path = $path;
        $this->basePath = $path;
        if ($base) {
            $this->basePath = $base;
        }
    }

    public function addIgnorePattern($pattern)
    {
        $this->ignore = array_merge($this->ignore, (array)$pattern);
    }

    public function scan()
    {
        $fileName = wp_tempnam();
        $fileHandle = fopen($fileName, 'w');

        $this->recScandir($this->path, $fileHandle, $this->basePath);
        fclose($fileHandle);

        $output =  $this->flatToJson(file_get_contents($fileName));
        unlink($fileName);
        return $output;
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

            $obj = new \stdClass();
            $obj->date = $cols[1];
            $obj->size = $cols[2];
            $obj->hash = $cols[3];
            $obj->mode = $cols[4];
            $obj->isDir = $cols[5];
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

            if ($this->fnInArray("$dir/$value", $this->ignore)) {
                continue;
            }

            if (is_file("$dir/$value")) {
                $this->fileInfo2File($f, "$dir/$value", $base);
                continue;
            }

            if ($this->includeFolderInfo) {
                $this->fileInfo2File($f, "$dir/$value", $base);
            }

            if ($this->recursive) {
                $this->recScandir("$dir/$value", $f, $base);
            }
        }
    }

    private function fileInfo2File($f, $file, $base)
    {
        $stat = stat($file);

        if ($this->calcHash) {
            $sum = md5_file($file);
        } else {
            $sum = 0;
        }

        $base = rtrim($base, '/') . '/';
        $relfile = substr($file, strlen($base));
        $row =  array(
            $relfile,
            $stat['mtime'],
            is_dir($file) ? 0 : $stat['size'],
            is_dir($file) ? 0 : $sum,
            substr(decoct($stat['mode']), -4),
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