<?php
namespace WPChecksum;

class MockApiClient
{
    const NO_APIKEY = 1;
    const INVALID_APIKEY = 2;
    const RATE_LIMIT_EXCEEDED = 3;
    const RESOURCE_NOT_FOUND = 4;
    const INVALID_EMAIL = 5;
    const EMAIL_IN_USE = 6;

    public function getQuota($apiKey = null)
    {

    }

    public function verifyApiKey($apiKey)
    {

    }

    public function registerEmail($email)
    {

    }

    public function getChecksums($type, $slug, $version)
    {
        return $this->orignialChecksums;
    }

    public function getFile($type, $slug, $version, $file)
    {
        $folder = 'remote-' . $type;

        $ret = array(
            'headers' => null,
            'response' => array(),
            'cookies' => array(),
            'filename' => null,
            'http_response' => null
        );

        $fileName = __DIR__ . "/fixtures/$folder/$file";
        if ($type != 'core') {
            $fileName = __DIR__ . "/fixtures/$folder/$slug/$file";
        }

        if (file_exists($fileName)) {
            $ret['response']['code'] = 200;
            $ret['body'] = file_get_contents($fileName);
        } else {
            $ret['response']['code'] = 404;
            $ret['response']['message'] = 'File not found';
            $ret['body'] = '';
        }
        return $ret;
    }

    ////////////
    public function setOriginalChecksums($checksums)
    {
        $this->orignialChecksums = $checksums;
    }

}