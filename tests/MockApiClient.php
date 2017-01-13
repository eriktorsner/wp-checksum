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

    }

    ////////////
    public function setOriginalChecksums($checksums)
    {
        $this->orignialChecksums = $checksums;
    }

}