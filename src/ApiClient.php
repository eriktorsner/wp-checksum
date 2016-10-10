<?php
namespace WPChecksum;

class ApiClient
{
    /**
     * Log object
     *
     * @var mixed
     */
    private $logger;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * ApiClient constructor.
     */
    public function __construct()
    {
        $app = Checksum::getApplication();
        $this->logger = $app['logger'];
        $this->baseUrl = $app['apiBaseUrl'];
    }

    /**
     * Get api rate limit quota for the current user
     *
     * @return null|object
     */
    public function getQuota()
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->logger->logError("No api key exists or can be created");
            return null;
        }

        $url = join('/', array($this->baseUrl, 'quota'));
        $args = array('headers' => array('Authorization' => $apiKey));
        $ret = wp_remote_get($url, $args);
        $out = null;

        if (is_wp_error($ret)) {
            return $out;
        }

        switch ($ret['response']['code']) {
            case 401:
                $out = null;
                $this->logger->logError('Invalid api key');
                break;
            case 200:
                $out = json_decode($ret['body']);
                break;
        }

        return $out;

    }


    /**
     * @param $type
     * @param $slug
     * @param $version
     * @return null|object
     */
    public function getChecksums($type, $slug, $version)
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->logger->logError("No api key exists or can be created");
            return null;
        }

        $url = join('/', array($this->baseUrl, 'checksum', $type, $slug, $version));
        $args = array('headers' => array('Authorization' => $apiKey));
        $out = wp_remote_get($url, $args);

        if (is_wp_error($out)) {
            return null;
        }

        switch ($out['response']['code']) {
            case 401:
                $out = null;
                $this->logger->logError('Invalid api key');
                break;
            case 404:
                $out = null;
                break;
            case 429:
                $out = null;
                $this->logger->logError('API rate limit reached');
                break;
            case 200:
                $out = json_decode($out['body']);
                if (isset($out->checksums)) {
                    $out->checksums = (array)$out->checksums;
                }
                break;
        }

        return $out;
    }

    /**
     * Read an API key from the WordPress DB or the wp-cli.yml file.
     *
     * If no API key is found, attempt to create a anonymous account
     * at wpessentials.io and store credentials in the WP db
     *
     * @return bool|string
     */
    private function getApiKey()
    {
        // Did we get a key via settings?
        $app = Checksum::getApplication();
        if (isset($app->apikey)) {
            return $app->apikey;
        }

        // perhaps this WP installation has a key?
        $apiKey = get_option('wp_checksum_apikey', false);
        if ($apiKey ) {
            return $apiKey;
        }

        // No? Let's see if we can create a key via the API
        $url = join('/', array($this->baseUrl, 'anonymoususer'));
        $out = wp_remote_post($url);
        if ($out['response']['code'] == 200) {
            $ret = json_decode($out['body']);
            $apiKey = base64_encode($ret->user . ':' . $ret->secret);
            update_option('wp_checksum_apikey', $apiKey);
            return $apiKey;
        }

        return false;
    }

}