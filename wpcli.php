<?php
require_once __DIR__ . '/ParametersProvider.php';

if (defined('WP_CLI') && class_exists('WP_CLI', false)) {
    WP_CLI::add_command('checksum', 'WPChecksum\Checksum');
}
