<?php

namespace WPChecksum;

class ThemeCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testLocal()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));

        $checker = new ThemeChecker(null, true);
        $out = $checker->check(
            'twentytwelve',
            array(
                'Name' => 'TwentyTwelve',
                'Version' => '2.2',
            )
        );

        $this->assertTrue(isset($out['type']));
        $this->assertTrue(isset($out['slug']));
        $this->assertTrue(isset($out['name']));
        $this->assertTrue(isset($out['version']));
        $this->assertTrue(isset($out['status']));
        $this->assertTrue(isset($out['changeset']));
        $this->assertEquals($out['type'], 'theme');
        $this->assertEquals($out['slug'], 'twentytwelve');
        $this->assertEquals($out['name'], 'TwentyTwelve');
        $this->assertEquals($out['version'], '2.2');
        $this->assertEquals(count($out['changeset']), 2);
        $this->assertTrue(isset($out['changeset']['readme.txt']));
        $this->assertEquals($out['changeset']['readme.txt']->isDir, 0);
        $this->assertEquals($out['changeset']['readme.txt']->isSoft, true);

        $this->assertTrue(isset($out['changeset']['archive.php']));
        $this->assertEquals($out['changeset']['archive.php']->isDir, 0);
        $this->assertEquals($out['changeset']['archive.php']->isSoft, false);

        $out = $checker->check(
            'premium',
            array(
                'Name' => 'Premium',
                'Version' => '1.6',
            )
        );

    }
}