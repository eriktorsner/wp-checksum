<?php

namespace WPChecksum;

use \Pimple\Container;

class WPRepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function testOne()
    {
        $repo = new WPRepository('integrity-checker', 'plugin');
        $this->assertTrue($repo->found());
        $this->assertEquals($repo->slug, 'integrity-checker');
        $this->assertEquals($repo->added, '2017-01-09');
        $this->assertEquals($repo->NotAProp, null);
    }
}