<?php
/**
 * Tests for the Container class.
 */
namespace Zewa\Tests;

use Zewa\Config;
use Zewa\Container;
use Zewa\Dependency;
use Zewa\HTTP\Request;

class DependencyTest extends \PHPUnit_Framework_TestCase
{
    public function testIsDependencyLoaded()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);

        $this->assertSame($dependency->isDependencyLoaded('\Zewa\Config'), true);
    }

    public function testIsDependencyNotLoaded()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);

        $this->assertSame($dependency->isDependencyLoaded('\Zewa\NotLoaded'), false);
    }

    public function testGetDependency()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);

        //we didn't have to load resolve config or persist it,
        //because it's injected and auto-stored internally

        $this->assertSame($dependency->getDependency('\Zewa\Config'), $config);
    }

    public function testDependencyResolution()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);
        // flash data increments, so it's not going to be exact same state..
        $this->assertEquals($dependency->resolve('\Zewa\HTTP\Request'), $request);
    }

    /**
     * Why won't ReflectionException throw ?
     */
    public function testMissingDependency()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $this->assertSame($dependency->resolve('\Zewa\DoesNotExist'), false);
    }

    public function testPersistentDependency()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);

        $dependency->resolve('\Zewa\Security', true);
        $this->assertSame($dependency->isDependencyLoaded('\Zewa\Security'), true);
    }

    public function testDependencyFlush()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);

        $dependency->flushDependency('\Zewa\Config');
        $dependency->resolve('\Zewa\Config');
    }
}
