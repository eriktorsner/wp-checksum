<?php

namespace WPChecksum;

use \Pimple\Container;

class BaseCheckerTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array(
            'apikey' => 'YWFmNjFhN2Y6NDAxMmE3OTQ0OQ==',
        )));

        $base = new BaseChecker(null, false);
    }

    public function testGetLocalChecksums()
    {
        $base = new BaseChecker(null, false);
        $out = $base->getLocalChecksums(__DIR__);

        $this->assertEquals($out->status, 200);
        $this->assertTrue(isset($out->checksums));
        $this->assertTrue(count($out->checksums) > 0);
        $this->assertTrue(isset($out->checksums['BaseCheckerTest.php']));
        $this->assertTrue(isset($out->checksums['BaseCheckerTest.php']->date));
        $this->assertTrue(isset($out->checksums['BaseCheckerTest.php']->size));
        $this->assertTrue(isset($out->checksums['BaseCheckerTest.php']->hash));
        $this->assertTrue(isset($out->checksums['BaseCheckerTest.php']->mode));
        $this->assertEquals($out->checksums['BaseCheckerTest.php']->isDir, 0);

    }

    public function testGetOriginalChecksums()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));

        $apiClient = $app['apiClient'];

        $base = new BaseChecker($apiClient, false);
        $fakeSums = $base->getLocalChecksums(__DIR__);
        $apiClient->setOriginalChecksums($fakeSums);

        $out = $base->getOriginalChecksums('plugin', 'integrity-checker', '1.0');

        $this->assertEquals($out->status, 200);
        $this->assertTrue(isset($out->checksums));
        $this->assertTrue(count($out->checksums) > 0);

    }

    public function testGetOriginalChecksumsLocal()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));

        $apiClient = $app['apiClient'];

        $base = new BaseChecker($apiClient, true);
        $out = $base->getOriginalChecksums('plugin', 'integrity-checker', '0.9.3');

        $this->assertEquals($out->status, 200);
        $this->assertTrue(isset($out->checksums));
        $this->assertTrue(count($out->checksums) > 0);

        $out = $base->getOriginalChecksums('theme', 'twentytwelve', '1.0');
        $this->assertEquals($out->status, 200);
        $this->assertTrue(isset($out->checksums));
        $this->assertTrue(count($out->checksums) > 0);


    }

    public function testGetChangeSet()
    {
        $base = new BaseChecker(null, false);
        $local = $base->getLocalChecksums(__DIR__);

        $original = clone $local;
        $changeSet = $base->getChangeSet($original, $local);
        $this->assertEquals(count($changeSet), 0);

        // Added a file to remote ===> trigger a DELETE locally
        $original = clone $local;
        $original->checksums['foobar'] = $original->checksums['BaseCheckerTest.php'];
        $changeSet = $base->getChangeSet($original, $local);
        $this->assertEquals(count($changeSet), 1);
        $this->assertEquals($changeSet['foobar']->status, 'DELETED');

        // Added a file to local ===> trigger a ADD locally
        $original = clone $local;
        $local->checksums['foobar2'] = $local->checksums['BaseCheckerTest.php'];
        $changeSet = $base->getChangeSet($original, $local);
        $this->assertEquals(count($changeSet), 1);
        $this->assertEquals($changeSet['foobar2']->status, 'ADDED');

        // Modify a local file
        $original = clone $local;
        $original->checksums['BaseCheckerTest.php'] = clone $local->checksums['BaseCheckerTest.php'];
        $local->checksums['BaseCheckerTest.php']->hash = 'abc123';
        $local->checksums['readme.txt'] = (object)array('hash' => 'f00');
        $original->checksums['readme.txt'] = (object)array('hash' => 'bar');


        $changeSet = $base->getChangeSet($original, $local);
        $this->assertEquals(count($changeSet), 2);
        $this->assertEquals($changeSet['BaseCheckerTest.php']->status, 'MODIFIED');

    }

}