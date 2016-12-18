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

        $pimple['settingsParser'] = function($pimple) {
            return $this;
        };

        $pimple['apiBaseUrl'] = 'https://api.wpessentials.io/v1';
        //$pimple['apiBaseUrl'] = 'http://api.wpessentials.local/v1';
    }

    public function log($message)
    {
        \WP_CLI::log($message);
    }

    public function logError($message)
    {
        \WP_CLI::error($message);
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

        $ret = $default;
        if (isset($cli->extra_config['checksum'][$name])) {
            $ret = $cli->extra_config['checksum'][$name];
            switch ($type) {
                case 'boolean':
                    $ret = filter_var($ret, FILTER_VALIDATE_BOOLEAN);
                    break;
            }

        }
        $ret = \WP_CLI\Utils\get_flag_value($cli->assoc_args, $name, $ret);

        return $ret;
    }
}