<?php

namespace WPChecksum;

use \Pimple\Container;

class ApiClientTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));

        // Not, do not get ApiClient via TestProvider
        $apiClient = new ApiClient();
    }
}