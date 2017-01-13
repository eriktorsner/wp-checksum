<?php
require_once __DIR__ . '/ParametersProvider.php';

if (defined('WP_CLI') && class_exists('WP_CLI', false)) {
    WP_CLI::add_command('checksum all', array('WPChecksum\Checksum', 'checkAll'));
    WP_CLI::add_command('checksum plugin', array('WPChecksum\Checksum', 'checkPlugin'));
    WP_CLI::add_command('checksum theme', array('WPChecksum\Checksum', 'checkTheme'));

    WP_CLI::add_command('checksum diff', array('WPChecksum\Checksum', 'diff'));

    WP_CLI::add_command('checksum quota', array('WPChecksum\Checksum', 'quota'));
    WP_CLI::add_command('checksum apikey', array('WPChecksum\Checksum', 'apikey'));
    WP_CLI::add_command('checksum register', array('WPChecksum\Checksum', 'register'));

}
