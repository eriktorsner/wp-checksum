<?php
namespace WPChecksum;

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
     * Internal object to retreive settings
     * @var object
     */
    private $settings;

    /**
     * @return \Pimple\Container
     */
    public static function getApplication()
    {
        if (!self::$application) {
            self::$application = new Container();
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
     * : What should we do report on? plugin|theme|quota. Omit to check everything
     * use quota to see API rate limits for current api key
     *
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
        $this->logger = $app['logger'];
        $this->settings = $app['settingsParser'];

        $type = 'all';
        if (count($args) > 0) {
            $type = $args[0];
            if (!in_array($type, array('plugin', 'theme', 'quota'))) {
                $this->logger->logError('Invalid type specified. Use one of plugin|theme or quota');
            }
        }

        $details = $this->settings->getSetting('details', 'boolean', false);
        $format = $this->settings->getSetting('format', 'string', 'table');
        if (!in_array($format, array('table', 'json', 'csv', 'yaml'))) {
            $this->logger->logError('Invalid format specified. Use one of table|json|csv|yaml');
        }

        switch ($type) {
            case 'all':
                $plugins = $this->checkPlugins($args);
                $themes = $this->checkThemes($args);
                $out = array_merge($plugins, $themes);
                break;
            case 'plugin':
                array_shift($args);
                $out = $this->checkPlugins($args);
                break;
            case 'theme':
                array_shift($args);
                $out = $this->checkThemes($args);
                break;
            case 'quota':
                $apiClient = new ApiClient();
                $out = $apiClient->getQuota();
                if ($out) {
                    $ret = array((array)$out);
                    $this->logger->formatItems($format, $ret, 'limit, current, resetIn');
                } else {

                }

                return;
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
            $checker = new PluginChecker($localCache);
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
            $checker = new ThemeChecker($localCache);
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