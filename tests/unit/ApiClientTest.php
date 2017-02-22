<?php

namespace WPChecksum;

use \Pimple\Container;

/**
 */
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

    public function testGetQuota()
    {
        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 401),
            ),
            array(
                'response' => array('code' => 200),
                'body' => json_encode('success'),
                'http_response' => new \MockHttpResponse(['foo' => 'bar', 'x-checksum-site-id' => 'abc123']),
            ),
            new \WP_Error(999, 'foobar'),
        ));

        $apiClient = new ApiClient();
        $ret = $apiClient->getQuota();
        $this->assertTrue(is_wp_error($ret));
        $ret = $apiClient->getQuota();
        $this->assertEquals($ret, 'success');
        $ret = $apiClient->getQuota();
        $this->assertTrue(is_wp_error($ret));

    }

    public function testGetApiKey()
    {
        setHttpMode('mock');
        $app = Checksum::getApplication();
        $settings = $app['settingsParser'];
        $settings->setSetting('apikey', false);

        setMockHttpResponse(array(
            array(
                'response' => array('code' => 400),
            ),
            array(
                'response' => array('code' => 401),
            ),
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array('user' => 'foo', 'secret' => 'bar'))
            ),
        ));

        $apiClient = new ApiClient();
        $ret = $apiClient->getQuota();
        $ret = $apiClient->getQuota();

        putenv('WP_CHKSM_APIKEY=foobar');
        $ret = $apiClient->getQuota();
        putenv('WP_CHKSM_APIKEY=');

        setMockOptions(array('yadya', 'abc123'));
        $ret = $apiClient->getQuota();

        $settings->setSetting('apikey', 'YAYAYAYAYA');

    }

    public function testVerifyApiKey()
    {
        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 401),
            ),
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array('code' => 200)),
            ),
        ));

        $apiClient = new ApiClient();
        $ret = $apiClient->verifyApiKey('');
        $this->assertTrue(is_wp_error($ret));
        $ret = $apiClient->verifyApiKey('');
        $this->assertEquals('API key updated', $ret->message);
        $ret = $apiClient->verifyApiKey('');
    }

    public function testRegisterEmail()
    {
        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 400),
            ),
            array(
                'response' => array('code' => 401),
            ),
            array(
                'response' => array('code' => 422),
            ),
            array(
                'response' => array(
                    'code' => 200,
                ),
                'body' => json_encode(array('some' => 'value')),
            ),
            new \WP_Error(999, 'foobar'),
        ));



        $apiClient = new ApiClient();

        setMockOptions(array('yadya', 'abc123'));
        $ret = $apiClient->registerEmail('');
        $this->assertTrue(is_wp_error($ret));
        $this->assertEquals(5, $ret->get_error_code());


        $ret = $apiClient->registerEmail('');
        $this->assertTrue(is_wp_error($ret));
        $this->assertEquals(2, $ret->get_error_code());

        $ret = $apiClient->registerEmail('');
        $this->assertTrue(is_wp_error($ret));
        $this->assertEquals(6, $ret->get_error_code());

        $ret = $apiClient->registerEmail('');
        $this->assertFalse(is_wp_error($ret));
        $this->assertEquals('value', $ret->some);

        $ret = $apiClient->registerEmail('');
        $this->assertTrue(is_wp_error($ret));

        // Test no API key
        $app = Checksum::getApplication();
        $settings = $app['settingsParser'];
        $settings->setSetting('apikey', false);
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 400),
            )
        ));
        $ret = $apiClient->registerEmail('');
        $this->assertTrue(is_wp_error($ret));
        $settings->setSetting('apikey', 'YAYAYAYAYA');

    }

    public function testGetChecksums()
    {
        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 401),
            ),
            array(
                'response' => array('code' => 404),
            ),
            array(
                'response' => array('code' => 429),
            ),
            array(
                'response' => array(
                    'code' => 200,
                ),
                'body' => json_encode(array('checksums' => 'value')),
            ),
            new \WP_Error(999, 'foobar'),
        ));

        $apiClient = new ApiClient();

        setMockOptions(array('abc123'));
        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertEquals(null, $ret);

        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertEquals(null, $ret);

        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertEquals(null, $ret);

        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertTrue(is_array($ret->checksums));

        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertTrue(is_null($ret));

        // Test no API key
        $app = Checksum::getApplication();
        $settings = $app['settingsParser'];
        $settings->setSetting('apikey', false);
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 400),
            )
        ));
        $ret = $apiClient->getChecksums('plugin', 'foobar', '1.0');
        $this->assertTrue(is_null($ret));
        $settings->setSetting('apikey', 'YAYAYAYAYA');

    }


    public function testGetFile()
    {
        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
            ),
        ));

        setMockOptions(array('abc123'));
        $apiClient = new ApiClient();
        $ret = $apiClient->getFile('', '', '', '');
        $this->assertTrue(is_array($ret));
        $this->assertTrue(isset($ret['response']));

        // Test no API key
        $app = Checksum::getApplication();
        $settings = $app['settingsParser'];
        $settings->setSetting('apikey', false);
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 400),
            )
        ));
        $ret = $apiClient->getFile('', '', '', '');
        $this->assertTrue(is_null($ret));
        $settings->setSetting('apikey', 'YAYAYAYAYA');

    }
}