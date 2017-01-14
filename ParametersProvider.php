<?php

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class ParametersProvider implements ServiceProviderInterface
{
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
            return new WPChecksum\ApiClient();
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
        $cli = \WP_CLI::get_runner();

        // Assume default
        $ret = $default;

        // If present in settings, override default
        if (isset($cli->extra_config['checksum'][$name])) {
            $ret = $cli->extra_config['checksum'][$name];
        }

        // If present on command line, override previous value
        $ret = \WP_CLI\Utils\get_flag_value($cli->assoc_args, $name, $ret);

        // Filter per type
        switch ($type) {
            case 'boolean':
                $ret = filter_var($ret, FILTER_VALIDATE_BOOLEAN);
                break;
        }

        return $ret;
    }
}