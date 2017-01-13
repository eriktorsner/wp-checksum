<?php

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class TestsProvider implements ServiceProviderInterface
{
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function register(Container $pimple)
    {
        $pimple['logger'] = function($pimple) {
            return $this;
        };

        $pimple['wrapper'] = function($pimple) {
            return $this;
        };

        $pimple['settingsParser'] = function($pimple) {
            return $this;
        };

        $pimple['apiClient'] = function($pimple) {
            require_once __DIR__ . '/MockApiClient.php';
            return new WPChecksum\MockApiClient();
        };


        $pimple['apiBaseUrl'] = $this->getSetting(
        	'apiBaseUrl',
            'string',
	        'https://api.wpessentials.io/v1'
        );

    }

    public function log($message)
    {
        \WP_CLI::log($message);
    }

    public function logError($message)
    {
        \WP_CLI::error($message);
    }

    public function colorLine($message)
    {
        \WP_CLI::line(\WP_CLI::colorize($message));
    }

    public function formatItems($format, $out, $cols)
    {
        \WP_CLI\Utils\format_items($format, $out, $cols);
    }

    /**
     * Get a settings from
     *   1. The command line, if not found
     *   2. The wp-cli.yml file, if not found
     *   3. The passed in default value
     *
     * @param $name
     * @param $type
     * @param $default
     * @return mixed
     */
    public function getSetting($name, $type, $default)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }

        return $default;
    }
}