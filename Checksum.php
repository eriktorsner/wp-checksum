<?php
namespace WPChecksum;

class Checksum
{
    /**
     * @var \Pimple\Container
     *
     */
    private static $application;

    /**
     * @return \Pimple\Container
     */
    public static function getApplication()
    {
        if (!self::$application) {
            self::$application = new \Pimple\Container();
            self::$application->register(new \ParametersProvider());
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
     * Checksum
     *
     * ## OPTIONS
     *
     * [<type>]
     * : What should we do checksums on? plugin|theme. Omit to check everything
     * [<slug>]
     * : Name of a specific plugin or theme to check. Leave blank to check all installed
     *
     * [--format=<table|json|csv|yaml>]
     * : Output format
     *
     * [--details]
     * : Set this to output a detailed changeset insetad of a summary
     *
     * [--localcache]
     * : Set this to force use of local checksums for origial or use remote service. Defaults to false/not set
     * ---q
     * default: false
     * options:
     *   - true
     *   - false
     * ---
     *
     * @param $args
     * @param $assocArgs
     *
     */
    public function __invoke($args, $assocArgs)
    {
        $app = self::getApplication();
        list($type, $details, $localCache, $format) = $this->parseArgs($args, $assocArgs);

        switch ($type) {
            case 'all':
                $plugins = $this->checkPlugins($args, $localCache);
                $themes = $this->checkThemes($args, $localCache);
                $out = array_merge($plugins, $themes);
                break;
            case 'plugin':
                array_shift($args);
                $out = $this->checkPlugins($args, $localCache);
                break;
            case 'theme':
                array_shift($args);
                $out = $this->checkThemes($args, $localCache);
                break;
            default:
                \WP_CLI::error('Invalid type specified. Use one of plugin|theme|core');
                break;
        }

        if ($details) {
            $this->outputDetailedResults($out, $format);
        } else {
            $this->outputSummaryResults($out, $format);
        }
    }

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
            \WP_CLI::log("Checking plugin $slug");
            $checker = new PluginChecker($localCache);
            $out[] = $checker->check($id, $plugin);
        }

        return $out;
    }

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
                \WP_CLI::log("Skipping theme $slug (child theme)");
                continue;
            }
            \WP_CLI::log("Checking theme $slug");
            $checker = new ThemeChecker($localCache);
            $out[] = $checker->check($slug, $theme);
        }

        return $out;
    }

    private function outputDetailedResults($data, $format)
    {
        $out = array();
        foreach ($data as $data) {
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
                    $row['Issues'] = array();
                    foreach ($data['changeset'] as $key => $change) {
                        $row['Issues'][$key] = $change->status;
                    }
                }
            } else {
                $row['Result'] = null;
                $row['Issues'] = null;
            }
            $out[] = $row;
        }

        \WP_CLI\Utils\format_items($format, $out, 'Type, Slug, Status, Version, Result, Issues');
    }

    private function outputSummaryResults($data, $format)
    {
        $out = array();
        foreach ($data as $data) {
            $row = array();
            $row['Type'] = $data['type'];
            $row['Slug'] = $data['slug'];
            $row['Version'] = $data['version'];
            $row['Status'] = ucfirst($data['status']);
            if ($data['status'] == 'checked') {
                $row['Result'] = count($data['changeset']) == 0? 'Ok': 'Changes detected';
                $row['Issues'] = count($data['changeset']);
            } else {
                $row['Result'] = null;
                $row['Issues'] = null;
            }

            $out[] = $row;
        }

        \WP_CLI\Utils\format_items($format, $out, 'Type, Slug, Status, Version, Result, Issues');
    }

    private function parseArgs($args, $assocArgs)
    {
        $cli = \WP_CLI::get_runner();

        $type = 'all';
        if (count($args) > 0) {
            $type = $args[0];
            if (!in_array($type, array('plugin', 'theme'))) {
                \WP_CLI::error('Invalid type specified. Use one of plugin|theme');
            }
        }

        $localCache = false;
        if (isset($cli->extra_config['checksum']['localcache'])) {
            $localCache = filter_var(
                $cli->extra_config['checksum']['localcache'], FILTER_VALIDATE_BOOLEAN
            );
        }
        $localCache = \WP_CLI\Utils\get_flag_value($assocArgs, 'localcache', false);

        $details = false;
        if (isset($cli->extra_config['checksum']['details'])) {
            $details = filter_var(
                $cli->extra_config['checksum']['details'], FILTER_VALIDATE_BOOLEAN
            );
        }
        $details = \WP_CLI\Utils\get_flag_value($assocArgs, 'details', false);

        $format = 'table';
        if (isset($cli->extra_config['checksum']['format'])) {
            $format = $cli->extra_config['checksum']['format'];
        }
        if (isset($assocArgs['format'])) {
            $format = $assocArgs['format'];
        }
        if (!in_array($format, array('table', 'json', 'csv', 'yaml'))) {
            \WP_CLI::error('Invalid format specified. Use one of table|json|csv|yaml');
        }

        return array($type, $details, $localCache, $format);
    }
}