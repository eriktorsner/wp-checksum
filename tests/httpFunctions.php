<?php
global $TEST_HTTP_MODE, $mockResponses;

$TEST_HTTP_MODE = 'real';

function setHttpMode($mode)
{
    global $TEST_HTTP_MODE;
    $TEST_HTTP_MODE = $mode;
}

function setMockHttpResponse($arr)
{
    global $mockResponses;
    $mockResponses = $arr;
}

function wp_remote_post($url, $args = null)
{
    global $TEST_HTTP_MODE;
    switch ($TEST_HTTP_MODE) {
        case 'real':
            return real_wp_remote_post($url, $args);
            break;
        case 'mock':
            return mock_wp_remote_post($url, $args);
            break;
    }
}

function wp_remote_get($url)
{
    global $TEST_HTTP_MODE;
    switch ($TEST_HTTP_MODE) {
        case 'real':
            return real_wp_remote_get($url);
            break;
        case 'mock':
            return mock_wp_remote_get($url);
            break;
    }
}

function real_wp_remote_post($url, $args)
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

function mock_wp_remote_post($url, $args)
{
    global $mockResponses;
    return array_shift($mockResponses);
}

function real_wp_remote_get($url)
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

function mock_wp_remote_get($url)
{
    global $mockResponses;
    return array_shift($mockResponses);
}