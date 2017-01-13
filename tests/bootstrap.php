<?php
require_once __DIR__.'/../vendor/autoload.php';

function wp_remote_post($url, $args)
{
    $postdata = http_build_query($args);
    $opts = array('http' =>
                      array(
                          'method'  => 'POST',
                          'header'  => 'Content-type: application/x-www-form-urlencoded',
                          'content' => $postdata,
                      )
    );

    $context  = stream_context_create($opts);
    $content = file_get_contents($url, false, $context);
    $codeParts = explode(' ', $http_response_header[0]);

    return [
        'response' => [
            'code' => $codeParts[1],
        ],
        'body' => $content,
    ];
}

function wp_remote_get($url)
{
    $content = @file_get_contents($url);
    $codeParts = explode(' ', $http_response_header[0]);

    return [
        'response' => [
            'code' => $codeParts[1],
        ],
        'body' => $content,
    ];
}

function get_theme_root()
{
    return __DIR__ . '/fixtures/themes';
}

function wp_tempnam()
{
    $dir = sys_get_temp_dir();
    return tempnam($dir, 'wpessapi');
}