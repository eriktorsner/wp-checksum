<?php

namespace WPChecksum;

class PluginCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        setHttpMode('real');
    }


    public function testLocal()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));

        $pluginChecker = new PluginChecker(null, true);
        $out = $pluginChecker->check(
            'hello-dolly',
            array(
                'Name' => 'Hello Dolly',
                'Version' => '1.6',
            )
        );

        $this->assertTrue(isset($out['type']));
        $this->assertTrue(isset($out['slug']));
        $this->assertTrue(isset($out['name']));
        $this->assertTrue(isset($out['version']));
        $this->assertTrue(isset($out['status']));
        $this->assertTrue(isset($out['changeset']));
        $this->assertEquals($out['type'], 'plugin');
        $this->assertEquals($out['slug'], 'hello-dolly');
        $this->assertEquals($out['name'], 'Hello Dolly');
        $this->assertEquals($out['version'], '1.6');
        $this->assertEquals(count($out['changeset']), 1);
        $this->assertTrue(isset($out['changeset']['readme.txt']));
        $this->assertEquals($out['changeset']['readme.txt']->isDir, 0);
        $this->assertEquals($out['changeset']['readme.txt']->isSoft, true);

        $out = $pluginChecker->check(
            'premium',
            array(
                'Name' => 'Premium',
                'Version' => '1.6',
            )
        );

    }
}