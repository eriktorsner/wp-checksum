<?php

namespace WPChecksum;

class ThemeCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        setHttpMode('real');
    }


    public function testLocal()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));

        $checker = new ThemeChecker(null, true);
        $theme = new \MockTheme(array(
            'Name' => 'TwentyTwelve',
            'Version' => '2.2',
            'stylesheet' => 'twentytwelve',
            'theme_root' => __DIR__ . '/fixtures/themes',
            'template' => 'twentytwelve',
        ));
        $out = $checker->check('twentytwelve', $theme);

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


        $theme = new \MockTheme(array(
            'Name' => 'Premium',
            'Version' => '2.2',
            'stylesheet' => 'premium',
            'theme_root' => __DIR__ . '/fixtures/themes',
            'template' => 'premium',
        ));
        $out = $checker->check('premium', $theme);

    }
}