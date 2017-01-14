<?php

namespace WPChecksum;

/**
 */
class ChecksumTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
    }

    public function testSetApplication()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));

        Checksum::setApplication($app);
    }

    public function testCheckPlugin()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));

        $logger = $app['logger'];
        $settings = $app['settingsParser'];

        setHttpMode('mock');
        $httpResponse = array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'checksums' => array('abc' => array('hash' => 'foo'))
                )),
            ),
        );
        $checksum = new Checksum();

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $checksum->checkPlugin(array(), array());
        $this->assertEquals(2, count($logger->output));
        $this->assertEquals('format_items', $logger->output[1]['cmd']);
        $this->assertEquals(3, $logger->output[1]['content'][0]['Issues']);

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $checksum->checkPlugin(array('hello-dolly'), array());


        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $checksum->checkPlugin(array('hello-dolly2'), array());
        $this->assertEquals(1, count($logger->output));

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $settings->setSetting('format', 'json');
        $checksum->checkPlugin(array(), array());


        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $settings->setSetting('format', 'yaml');
        $checksum->checkPlugin(array(), array());

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $settings->setSetting('format', 'csv');
        $checksum->checkPlugin(array(), array());

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $settings->setSetting('format', 'foobar');
        $checksum->checkPlugin(array(), array());
        $this->assertEquals('error', $logger->output[0]['cmd']);
        $this->assertTrue(strpos($logger->output[0]['content'], 'Invalid') !== false);
        $settings->setSetting('format', 'table');
    }

    public function testCheckTheme()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));

        $logger = $app['logger'];

        setHttpMode('mock');
        $httpResponse = array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'checksums' => array('abc' => array('hash' => 'foo'))
                )),
            ),
        );
        $checksum = new Checksum();

        $logger->output = array();
        setMockHttpResponse($httpResponse);
        $checksum->checkTheme(array(), array());

        $this->assertEquals(3, count($logger->output));
        $this->assertTrue(strpos($logger->output[1]['content'], 'Skipping') !== false);
        $this->assertEquals('format_items', $logger->output[2]['cmd']);
        $this->assertEquals(1, count($logger->output[2]['content']));
        $this->assertEquals(38, $logger->output[2]['content'][0]['Issues']);

    }

    public function testCheckAll()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));


        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'checksums' => array('abc' => array('hash' => 'foo'))
                )),
            ),
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'checksums' => array('abc' => array('hash' => 'foo'))
                )),
            ),
            array(
                'response' => array('code' => 200),
            ),
        ));
        $checksum = new Checksum();
        $checksum->checkAll(array(), array());
    }

    public function testQuota()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));
        $logger = $app['logger'];
        $settings = $app['settingsParser'];

        setHttpMode('mock');
        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'validationStatus' => 'VALIDATED',
                )),
            ),
            new \WP_Error(400, 'foobar'),
        ));

        $checksum = new Checksum();
        $logger->output = array();
        $checksum->quota(array(), array());
        $this->assertTrue(count($logger->output) == 1);
        $this->assertEquals('VALIDATED', $logger->output[0]['content'][0]['status']);

        $logger->output = array();
        $checksum->quota(array(), array());
        $this->assertTrue(count($logger->output) == 1);
        $this->assertEquals('error', $logger->output[0]['cmd']);
    }

    public function testApiKey()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));
        $logger = $app['logger'];
        $settings = $app['settingsParser'];

        setHttpMode('mock');

        $checksum = new Checksum();
        $logger->output = array();
        $checksum->apiKey(array('get'), array());
        $this->assertEquals(3, count($logger->output));

        setMockOptions(array('SFSDF'));
        $logger->output = array();
        $checksum->apiKey(array('get'), array());
        $this->assertEquals(1, count($logger->output));

        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'validationStatus' => 'VALIDATED',
                )),
            ),
            new \WP_Error(400, 'foobar'),
        ));

        // Wrong nr of args
        $logger->output = array();
        $checksum->apiKey(array('set'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);

        // Successful set
        $logger->output = array();
        $checksum->apiKey(array('set', 'FOOBAR'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('log', $logger->output[0]['cmd']);

        // Unsuccessful set
        $logger->output = array();
        $checksum->apiKey(array('set', 'FOOBAR'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);

    }

    public function testRegister()
    {
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));
        $logger   = $app['logger'];
        $settings = $app['settingsParser'];

        setHttpMode('mock');

        $checksum = new Checksum();

        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
                'body' => json_encode(array('some' => 'value')),
            ),
            new \WP_Error(400, 'foobar'),
        ));

        // Wrong nr of arguments
        $logger->output = array();
        $checksum->register(array(), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);

        // Invalid email
        $logger->output = array();
        $checksum->register(array('fooexample.com'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);

        // Successful
        $logger->output = array();
        $checksum->register(array('foo@example.com'), array());
        $this->assertEquals(2, count($logger->output));
        $this->assertEquals('log', $logger->output[0]['cmd']);

        // failed
        $logger->output = array();
        $checksum->register(array('foo@example.com'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);


    }

    public function testDiff()
    {
        $app      = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));
        $logger   = $app['logger'];
        $settings = $app['settingsParser'];

        setHttpMode('mock');

        $checksum = new Checksum();

        setMockHttpResponse(array(
            array(
                'response' => array('code' => 200),
                'body'     => "<?php\nfoobar();\n",
            ),
            array(
                'response' => array('code' => 200),
                'body'     => "<?php\nfoobar();\n",
            ),
        ));

        // invalid type
        $logger->output = array();
        $checksum->diff(array('foobar', 'wp-blog-header.php'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);

        // core
        $logger->output = array();
        $checksum->diff(array('core', 'wp-blog-header.php'), array());
        $this->assertTrue(count($logger->output) > 10);

        // plugin
        $logger->output = array();
        $checksum->diff(array('plugin', 'hello-dolly', 'hello.php'), array());
        $this->assertTrue(count($logger->output) > 10);

        $logger->output = array();
        $checksum->diff(array('plugin', 'hello-dolly', 'hello2.php'), array());
        $this->assertEquals(1, count($logger->output));
        $this->assertEquals('error', $logger->output[0]['cmd']);
    }

}