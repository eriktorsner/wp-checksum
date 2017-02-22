<?php
namespace WPChecksum;

/**
 * Class ApiClient
 * @package integrityChecker
 */
class ApiClient
{
    const NO_APIKEY = 1;
    const INVALID_APIKEY = 2;
    const RATE_LIMIT_EXCEEDED = 3;
    const RESOURCE_NOT_FOUND = 4;
    const INVALID_EMAIL = 5;
    const EMAIL_IN_USE = 6;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var object
     */
    private $lastError;

    /**
     * Internal log object
     * @var object
     */
    private $logger;

    /**
     * Internal object to retreive settings
     * @var object
     */
    private $settings;

    /**
     * ApiClient constructor.
     */
    public function __construct()
    {
        $app = Checksum::getApplication();
        $this->logger = $app['logger'];
        $this->baseUrl = $app['apiBaseUrl'];
        $this->settings = $app['settingsParser'];
    }

    /**
     * Get api rate limit quota for the current user
     *
     * @param string|null $apiKey
     *
     * @return null|object
     */
    public function getQuota($apiKey = null)
    {
        $this->lastError = 0;
        if (!$apiKey) {
            $apiKey = $this->getApiKey();
        }

        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
        }

        $url = join('/', array($this->baseUrl, 'quota'));
        $args = array('headers' => $this->headers($apiKey));
        $ret = wp_remote_get($url, $args);
        $this->updateSiteId($ret);

        if (is_wp_error($ret)) {
            return $ret;
        }

        $out = null;

        switch ($ret['response']['code']) {
            case 401:
                $this->lastError = self::INVALID_APIKEY;
                $out = new \WP_Error(self::INVALID_APIKEY, 'Invalid API key');
                break;
            case 200:
                $out = json_decode($ret['body']);
                break;
        }

        return $out;

    }

    /**
     * Verify that an Apikey is valid
     *
     * @param string $apiKey
     *
     * @return object|\WP_Error
     */
    public function verifyApiKey($apiKey)
    {
        $ret = $this->getQuota($apiKey);
        if (is_null($ret)) {
            return new \WP_Error(400, 'Unknown error');
        }

        if ($this->lastError === 0) {
            update_option('wp_checksum_apikey', $apiKey);
            $ret->message = 'API key updated';
            return $ret;
        } else {
            return new \WP_Error(400, 'API key verification failed. Key not updated');
        }
    }

    /**
     * Register email address with backend
     * (increases quota)
     *
     * @param string $email
     *
     * @return object|\WP_Error
     */
    public function registerEmail($email)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->lastError = self::NO_APIKEY;
            return  new \WP_Error(self::NO_APIKEY, 'No API key');
        }

        $url = join('/', array($this->baseUrl, 'userdata'));
        $args = array(
            'headers' => $this->headers($apiKey, array('Content-Type' => 'application/json')),
            'body' => json_encode(array(
                'email' => $email,
                'host' => get_site_url(),
            )),
        );

        $siteId = get_option('wp_checksum_siteid', false);
        if ($siteId) {
            $args['headers']['X-Checksum-Site-Id'] = $siteId;
        }

        $args = http_build_query($args);
        $out = wp_remote_post($url, $args);
        $this->updateSiteId($out);

        if (is_wp_error($out)) {
            return $out;
        }

        switch ($out['response']['code']) {
            case 400:
                $this->lastError = self::INVALID_EMAIL;
                $out = new \WP_Error(self::INVALID_EMAIL, 'Invalid email format or domain');
                break;
            case 401:
                $this->lastError = self::INVALID_APIKEY;
                $out = new \WP_Error(self::INVALID_APIKEY, 'Invalid API key');
                break;
            case 422:
                $this->lastError = self::EMAIL_IN_USE;
                $out = new \WP_Error(self::EMAIL_IN_USE, 'Email address already in use');
                break;
            case 200:
                $out = json_decode($out['body']);
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
        $this->lastError = 0;
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            $this->logger->logError("No api key exists or can be created");
            return null;
        }

        $url = join('/', array($this->baseUrl, 'checksum', $type, $slug, $version));
        $args = array('headers' => $this->headers($apiKey));
        $out = wp_remote_get($url, $args);
        $this->updateSiteId($out);

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
     * Get original source file of a plugin/theme
     *
     * @param string $type
     * @param string $slug
     * @param string $version
     * @param string $file
     *
     * @return array|null|\WP_Error
     */
    public function getFile($type, $slug, $version, $file)
    {
        $this->lastError = 0;
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->logger->logError("No api key exists or can be created");
            return null;
        }

        $url = join('/', array($this->baseUrl, 'file', $type, $slug, $version));
        $args = array(
            'headers' => $this->headers($apiKey, array('X-Filename' => $file)),
        );

        $out = wp_remote_get($url, $args);
        $this->updateSiteId($out);

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
        // Did we get a key via settings (command line or yml file)?
        $apiKey = $this->settings->getSetting('apikey', 'string', false);
        if ($apiKey) {
            return $apiKey;
        }

        // no? Do we have an environment variable?
        $apiKey = getenv('WP_CHKSM_APIKEY');
        if ($apiKey) {
            return $apiKey;
        }


        // perhaps this WP installation has a key?
        $apiKey = get_option('wp_checksum_apikey', false);
        if ($apiKey ) {
            return $apiKey;
        }

        // No? Let's see if we can create a key via the API
        $url = join('/', array($this->baseUrl, 'anonymoususer'));
        $out = wp_remote_post($url);
        $this->updateSiteId($out);
        if ($out['response']['code'] == 200) {
            $ret = json_decode($out['body']);
            $apiKey = base64_encode($ret->user . ':' . $ret->secret);
            update_option('wp_checksum_apikey', $apiKey);

            return $apiKey;
        }

        return false;
    }

    /**
     * Check if the server wants us to set a new siteid
     *
     * @param $response
     */
    private function updateSiteId($response)
    {
        if (is_wp_error($response)) {
            return;
        }

        if (!isset($response['http_response'])) {
            return;
        }
        $objHeaders = $response['http_response'];
        $headers = $objHeaders->get_headers()->getAll();
        if (isset($headers['x-checksum-site-id'])) {
            update_option('wp_checksum_siteid', $headers['x-checksum-site-id']);
        }
    }

    /**
     * Prepare standard headers
     *
     * @param $apiKey
     * @param $arr
     *
     * @return array
     */
    private function headers($apiKey, $arr = array())
    {
        $ret = array(
            'Authorization' => $apiKey,
            'X-Checksum-Client' => 'wp-checksum; ' . WP_CHECKSUM_VERSION,
        );
        $siteId = get_option('wp_checksum_siteid', false);
        if ($siteId) {
            $ret['X-Checksum-Site-Id'] = $siteId;
        }

        foreach ($arr as $key => $value) {
            $ret[$key] = $value;
        }

        return $ret;
    }
}