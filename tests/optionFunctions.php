<?php
global $mockOptions;

function update_option($key, $value, $prev = null)
{

}

function setMockOptions($arr)
{
    global $mockOptions;
    $mockOptions = $arr;

}

function get_option($key, $default)
{
    global $mockOptions;
    if (is_array($mockOptions) && count($mockOptions)) {
        return array_shift($mockOptions);
    }

    return $default;
}