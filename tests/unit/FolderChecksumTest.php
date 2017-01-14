<?php

namespace WPChecksum;

/**
 */
class FolderChecksumTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $folderChecksum = new FolderChecksum(__DIR__);
        $folderChecksum = new FolderChecksum(__DIR__, dirname(__DIR__));

    }

    public function testAddIgnorePattern()
    {
        $folderChecksum = new FolderChecksum(dirname(__DIR__) . '/fixtures/core');
        $ret = $folderChecksum->scan();
        $countStandard = count($ret->checksums);

        $folderChecksum->addIgnorePattern(array('/wp-admin*'));
        $ret = $folderChecksum->scan();
        $this->assertTrue($countStandard > count($ret->checksums));
    }

    public function testScan()
    {
        $folderChecksum = new FolderChecksum(dirname(__DIR__) . '/fixtures/core');
        $ret = $folderChecksum->scan();
        $this->assertEquals(200, $ret->status);
        $this->assertTrue(isset($ret->checksums));
        $this->assertTrue(is_array($ret->checksums));
        $countStandard = count($ret->checksums);

        $folderChecksum = new FolderChecksum(dirname(__DIR__) . '/fixtures/core');
        $folderChecksum->includeFolderInfo = true;
        $ret = $folderChecksum->scan();
        $this->assertEquals(200, $ret->status);
        $this->assertTrue(isset($ret->checksums));
        $this->assertTrue(is_array($ret->checksums));
        $this->assertTrue($countStandard < count($ret->checksums));

        $folderChecksum = new FolderChecksum(dirname(__DIR__) . '/fixtures/core');
        $folderChecksum->calcHash= false;
        $ret = $folderChecksum->scan();
        $this->assertEquals(200, $ret->status);
        $this->assertTrue(isset($ret->checksums));
        $this->assertTrue(is_array($ret->checksums));
        $this->assertEquals('0', reset($ret->checksums)->hash);

        $folderChecksum = new FolderChecksum(dirname(__DIR__) . '/fixtures/core');
        $folderChecksum->recursive= false;
        $ret = $folderChecksum->scan();
        $this->assertEquals(200, $ret->status);
        $this->assertTrue(isset($ret->checksums));
        $this->assertTrue(is_array($ret->checksums));
        $this->assertTrue($countStandard > count($ret->checksums));


    }
}