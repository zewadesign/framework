<?php

namespace Zewa\Config\Tests;

use Zewa\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testContainerStorage()
    {
        $container = new Container();

        $container->set('aContainer', ['hello' => 'world']);

        $array = $container->get('aContainer');

        $this->assertSame($array, ['hello' => 'world']);
    }

    /**
     * @expectedException \Zewa\Exception\LookupException
     */
    public function testContainerLookupException()
    {
        $container = new Container();
        $container->get('aContainer');
    }


}