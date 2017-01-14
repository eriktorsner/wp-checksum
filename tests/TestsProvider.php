<?php

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class TestsProvider implements ServiceProviderInterface
{
    public $output = array();

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

    public function resetLog()
    {
        $this->output = array();
    }

    public function log($message)
    {
        $this->output[] = array(
            'cmd'     => 'log',
            'content' => $message
        );
    }

    public function logError($message)
    {
        $this->output[] = array(
            'cmd'     => 'error',
            'content' => $message
        );
    }

    public function colorLine($message)
    {
        $this->output[] = array(
            'cmd'     => 'line',
            'content' => 'colorize:'  . $message
        );
    }

    public function formatItems($format, $out, $cols)
    {
        $this->output[] = array(
            'cmd'     => 'format_items',
            'content' => $out,
        );

    }

    public function setSetting($name, $value)
    {
        $this->settings[$name] = $value;
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