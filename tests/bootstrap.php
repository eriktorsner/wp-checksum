<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/class-wp-error.php';
require_once __DIR__.'/httpFunctions.php';
require_once __DIR__.'/optionFunctions.php';

define('ABSPATH', __DIR__ . '/fixtures/core/');
define('WPINC', 'wp-includes');
define('WP_PLUGIN_DIR', __DIR__ . '/fixtures/plugins');


function get_theme_root()
{
    return __DIR__ . '/fixtures/themes';
}

function wp_tempnam()
{
    $dir = sys_get_temp_dir();
    return tempnam($dir, 'wpessapi');
}

function get_plugins()
{
    return array(
        'hello-dolly/hello.php' => array(
            'Name' => 'Hello Dolly',
            'Version' => '1.6',
        ),
    );
}

function is_wp_error( $thing ) {
    return ( $thing instanceof WP_Error );
}

function get_site_url()
{
    return 'http://test.example.com';
}

class MockTheme {
    public function __construct($variables)
    {
        $this->variables = $variables;
    }

    public function get($name)
    {
        return $this->variables[$name];
    }

    public function __get($name)
    {
        return $this->variables[$name];
    }
}

function wp_get_themes()
{
    return array(
        'twentytwelve' => new MockTheme(array(
            'Name' => 'TwentyTwelve',
            'Version' => '1.2',
            'stylesheet' => 'twentytwelve',
            'theme_root' => __DIR__ . '/fixtures/themes',
            'template' => 'twentytwelve',
        )),
        'twentytwelveLocalOnly' => new MockTheme(array(
            'Name' => 'TwentyTwelveLocal',
            'Version' => '1.2',
            'stylesheet' => 'twentytwelvelocal',
            'theme_root' => __DIR__ . '/fixtures/themes',
            'template' => 'twentytwelve',
        )),
    );
}

class MockHttpResponse
{
    public function __construct($arr)
    {
        $this->headers = $arr;
    }

    public function get_headers()
    {
        return new MockDict($this->headers);
    }
}

class MockDict
{
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function getAll()
    {
        return $this->arr;
    }
}
