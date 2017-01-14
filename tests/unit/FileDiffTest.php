<?php

namespace WPChecksum;

class FileDiffTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $fileDiff = new FileDiff(null);
        $this->assertTrue(isset($fileDiff));
    }

    public function testGetDiffCore()
    {
        setHttpMode('real');

        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));
        $wrapper = $app['wrapper'];
        $wrapper->resetLog();

        $apiClient = $app['apiClient'];
        $fileDiff = new FileDiff($apiClient);

        // test no diff
        $ret = $fileDiff->getDiff('core', 'core', 'wp-blog-header.php', 'diff');
        $this->assertEquals(count($wrapper->output), 2);
        $this->assertEquals($wrapper->output[1]['content'], 'colorize:Empty diff');

        // test a modified file
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('core', 'core', 'wp-admin/about.php', 'diff');
        $this->assertEquals(24, count($wrapper->output));
        $this->assertFalse($wrapper->output[1]['content'] == 'colorize:Empty diff');

        // test a file that exists remote but not locally
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('core', 'core', 'foofile.php', 'diff');
        $this->assertEquals(400, $ret->get_error_code());

        // test a file that not exists remotely
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('core', 'core', 'foofile2.php', 'diff');
        $this->assertEquals(404, $ret->get_error_code());

    }

    public function testGetDiffPlugin()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));
        $wrapper = $app['wrapper'];

        $apiClient = $app['apiClient'];
        $fileDiff = new FileDiff($apiClient);

        // test a modified file
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('plugin', 'hello-dolly', 'hello.php', 'diff');
        $this->assertEquals(5, count($wrapper->output));
        $this->assertFalse($wrapper->output[1]['content'] == 'colorize:Empty diff');

        // test a non existing local plugin
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('plugin', 'hello-dolly2', 'hello.php', 'diff');
        $this->assertEquals(400, $ret->get_error_code());
        $this->assertEquals('Local plugin not found', $ret->get_error_message());
    }

    public function testGetDiffTheme()
    {
        require_once __DIR__ . '/../TestsProvider.php';
        $app = Checksum::getApplication(new \TestsProvider(array()));
        $wrapper = $app['wrapper'];

        $apiClient = $app['apiClient'];
        $fileDiff = new FileDiff($apiClient);

        // test a modified file
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('theme', 'twentytwelve', 'content.php', 'diff');
        $this->assertEquals(3, count($wrapper->output));
        $this->assertFalse($wrapper->output[1]['content'] == 'colorize:Empty diff');

        // test a non existing local theme
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('theme', 'twentytwelve2', 'hello.php', 'diff');
        $this->assertEquals(400, $ret->get_error_code());
        $this->assertEquals('Local theme not found', $ret->get_error_message());

        // test a non existing remote theme
        $wrapper->output = array();
        $ret = $fileDiff->getDiff('theme', 'twentytwelveLocalOnly', 'content.php', 'diff');
        $this->assertEquals(404, $ret->get_error_code());
    }

}