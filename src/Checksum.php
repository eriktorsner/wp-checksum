<?php
namespace WPChecksum;

use integrityChecker\FileDiff;
use Pimple\Container;

class Checksum
{
    /**
     * @var \Pimple\Container
     *
     */
    private static $application;

    /**
     * Internal log object
     * @var object
     */
    private $logger;

    /**
     * Internal object to retrieve settings
     * @var object
     */
    private $settings;


	/**
	 * One global client
	 * @var ApiClient
	 */
	private $apiClient;

    /**
     * @param  \Pimple\ServiceProviderInterface $provider
     *
     * @return \Pimple\Container
     */
    public static function getApplication($provider = null)
    {
        if (!self::$application) {
            self::$application = new Container();
            if (!$provider) {
                $provider = new \ParametersProvider();
            }
            self::$application->register($provider);
        }

        return self::$application;
    }

    /**
     * @param \Pimple\Container $application
     */
    public static function setApplication($application)
    {
        self::$application = $application;
    }


    /**
     * Verify integrity of plugins and themes by comparing file checksums
     *
     * ## OPTIONS
     *
     * [--format=<table|json|csv|yaml>]
     * : Output format
     *
     * [--details]
     * : Set this to output a detailed change set instead of a summary
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function checkAll($args, $assocArgs)
    {
        $this->check('all', $args, $assocArgs);
    }

    /**
     * Verify integrity of all plugins by comparing file checksums
     *
     * ## OPTIONS
     *
     * [<slug>]
     * : Optional. The slug of the theme, if omitted, check all themes
     *
     * [--format=<table|json|csv|yaml>]
     * : Output format
     *
     * [--details]
     * : Set this to output a detailed change set instead of a summary
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function checkPlugin($args, $assocArgs)
    {
        $this->check('plugin', $args, $assocArgs);
    }

    /**
     * Verify integrity of all themes by comparing file checksums
     *
     * ## OPTIONS
     *
     * [<slug>]
     * : Optional. The slug of the theme, if omitted, check all themes
     *
     * [--format=<table|json|csv|yaml>]
     * : Output format
     *
     * [--details]
     * : Set this to output a detailed change set instead of a summary
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function checkTheme($args, $assocArgs)
    {
        $this->check('theme', $args, $assocArgs);
    }

    /**
     * Print API rate limits for current api key
     *
     * ## OPTIONS
     *
     * [--format=<table|json|csv|yaml>]
     * : Output format
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function quota($args, $assocArgs)
    {
        $app = self::getApplication();
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        $format = $this->settings->getSetting('format', 'string', 'table');
        if (!in_array($format, array('table', 'json', 'csv', 'yaml'))) {
            $this->logger->logError('Invalid format specified. Use one of table|json|csv|yaml');
            return;
        }

        $apiClient = new ApiClient();
        $out = $apiClient->getQuota();
        if (!is_wp_error($out)) {
            $ret = array((array)$out);
            $ret[0]['status'] = $ret[0]['validationStatus'];
            $this->logger->formatItems($format, $ret, 'limit, current, resetIn, status, email');
        } else {
            $this->logger->logError($out->get_error_message());
            return;
        }
    }

    /**
     * Get or set the api key stored in WordPress options table
     *
     * ## OPTIONS
     *
     * <action>
     * : get or set
     *
     * [<apikey>]
     * : The new api key value, mandatory when action=set
     *
     * @param $args
     * @param $assocArgs
     */
    public function apikey($args, $assocArgs)
    {
        $app = self::getApplication();
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        switch ($args[0]) {
            case 'get':
                $apiKey = get_option('wp_checksum_apikey', false);
                if ($apiKey ) {
                    $this->logger->log($apiKey);
                } else {
                    $this->logger->log('No API key stored in WordPress. An anonymous key will be generated');
                    $this->logger->log('the first time wp-checksum is used. Or you can specify a key using');
                    $this->logger->log('wp checksum apikey set');
                }
                break;
            case 'set';
                if (count($args) != 2) {
                    $this->logger->logError('Usage: wp checksum apikey set <apikey>');
                    return;
                }
                $newKey = $args[1];
                $apiClient = new ApiClient();
                $result = $apiClient->verifyApiKey($newKey);
                if (is_wp_error($result)) {
                    $this->logger->logError($result->get_error_message());
                    return;
                } else {
                    $this->logger->log('New api key stored!');
                }
                break;
        }
    }

    /**
     * Register email address for the current api key to increase hourly quota.
     *
     * ## OPTIONS
     *
     * <email>
     * : Email address
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function register($args, $assocArgs)
    {
        $app = self::getApplication();
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        if (count($args) != 1) {
            $this->logger->logError('Usage: wp checksum register <email>');
            return;
        }

        $email = $args[0];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->logError("Invalid email address");
            return;
        }

        $apiClient = new ApiClient();
        $result = $apiClient->registerEmail($email);
        if (is_wp_error($result)) {
            $this->logger->logError($result->get_error_message());
            return;
        } else {
            $this->logger->log('Email address registered. Please check your inbox for the validation email');
            $this->logger->log('and click the link to validate your email address.');
        }

    }

    /**
     * Diff a file in your local WordPress install with it's original from
     * the WordPress.org repository
     *
     * ## OPTIONS
     *
     * <type>
     * : core, plugin or theme
     *
     * [<slug>]
     * : The slug to identify the plugin or theme. Skip this arg for core files
     *
     * <path>
     * : The relative path of the file to check
     *
     * [--apikey]
     * : Specify the api key to use. The api key will be read from (in priority):
     *   1. Command line arguments
     *   2. The wp-cli.yml file
     *   3. Environment variable WP_CHKSM_APIKEY
     *   4. Local WordPress options table
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function diff($args, $assocArgs)
    {
        $app = self::getApplication();
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        $type = $args[0];
        if (!in_array($type, array('core', 'plugin', 'theme'))) {
            $this->logger->logError("Type must be one of core, plugin or theme");
            return;
        }

        if ($type == 'core') {
            $slug = 'core';
            $file = $args[1];
        } else {
            $slug = $args[1];
            $file = $args[2];
        }

        $bin = 'diff';

        $fileDiff = new \WPChecksum\FileDiff(new ApiClient());
        $ret = $fileDiff->getDiff($type, $slug, $file, $bin);

        if (is_wp_error($ret)) {
            // no diff was possible and no output would have been made, error out
            $this->logger->logError($ret->get_error_message());
            return;
        }
    }

    /**
     * Internal check
     *
     * @param $type
     * @param $args
     * @param $assocArgs
     *
     */
    private function check($type, $args, $assocArgs)
    {
        $app = self::getApplication();
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        $details = $this->settings->getSetting('details', 'boolean', false);
        $format = $this->settings->getSetting('format', 'string', 'table');
        if (!in_array($format, array('table', 'json', 'csv', 'yaml'))) {
            $this->logger->logError('Invalid format specified. Use one of table|json|csv|yaml');
            return;
        }

	    $this->apiClient = new ApiClient();

        switch ($type) {
            case 'all':
                $plugins = $this->checkPlugins($args);
                $themes = $this->checkThemes($args);
                $out = array_merge($plugins, $themes);
                break;

            case 'plugin':
                //array_shift($args);
                $out = $this->checkPlugins($args);
                break;

            case 'theme':
                //array_shift($args);
                $out = $this->checkThemes($args);
                break;
        }

        $this->outputResults($out, $format, $details);
    }

    /**
     * Run checksums for all plugins or a single named plugin
     *
     * @param $args
     * @param bool $localCache
     *
     * @return array
     */
    private function checkPlugins($args, $localCache = false)
    {
        $scope = 'all';

        if (count($args) > 0) {
            $scope = $args[0];
        }

        $plugins = get_plugins();
        $out = array();
        foreach ($plugins as $id => $plugin) {
            $parts = explode('/', $id);
            $slug = $parts[0];
            if ($scope != 'all' && $scope != $slug) {
                continue;
            }

            $this->logger->log("Checking plugin $slug");
            $checker = new PluginChecker($this->apiClient, $localCache);
            $out[] = $checker->check($id, $plugin);
        }

        return $out;
    }

    /**
     * Run checksums for all themes or a single named theme
     *
     * @param $args
     * @param bool $localCache
     *
     * @return array
     */
    private function checkThemes($args, $localCache = false)
    {
        $scope = 'all';
        if (count($args) > 0) {
            $scope = $args[0];
        }
        $themes = wp_get_themes();
        $out = array();
        foreach ($themes as $slug => $theme) {
            if ($scope != 'all' && $scope != $slug) {
                continue;
            }
            // Don't attempt to check child themes
            if ($theme->template != $slug) {
                $this->logger->log("Skipping theme $slug (child theme)");
                continue;
            }

            $this->logger->log("Checking theme $slug");
            $checker = new ThemeChecker($this->apiClient, $localCache);
            $out[] = $checker->check($slug, $theme);
        }

        return $out;
    }

    /**
     * Output checksum results
     *
     * @param array $dataRows  The list of plugins/thems that have been checked
     * @param string $format   table|csv|json|yaml
     * @param boolean $details true|false
     */
    private function outputResults($dataRows, $format, $details)
    {
        $out = array();
        foreach ($dataRows as $data) {
            $row = array();
            $row['Type'] = $data['type'];
            $row['Slug'] = $data['slug'];
            $row['Version'] = $data['version'];
            $row['Status'] = ucfirst($data['status']);
            if ($data['status'] == 'checked') {
                if (count($data['changeset']) == 0) {
                    $row['Result'] = 'Ok';
                    $row['Issues'] = null;
                } else {
                    $row['Result'] = 'Changes detected';
                    if ($details) {
                        $row['Issues'] = array();
                        foreach ($data['changeset'] as $key => $change) {
                            $row['Issues'][$key] = $change->status;
                        }
                    } else {
                        $row['Issues'] = count($data['changeset']);
                    }
                }
            } else {
                $row['Result'] = null;
                $row['Issues'] = null;
            }
            $out[] = $row;
        }

        $this->logger->formatItems($format, $out, 'Type, Slug, Status, Version, Result, Issues');
    }
}