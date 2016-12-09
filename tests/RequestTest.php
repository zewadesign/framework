<?php
/**
 * Tests for the Request class.
 */
namespace Zewa\Tests;

use Zewa\Config;
use Zewa\Container;
use Zewa\Dependency;
use Zewa\HTTP\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testSetDeleteRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $request->delete->set('hello', 'world');

        $this->assertSame($request->delete->fetch('hello'), 'world');
    }

    public function testSetPutRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $request->put->set('hello', 'world');

        $this->assertSame($request->put->fetch('hello'), 'world');
    }

    public function testSetRequestRouteRequest()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $request->setRequest('\Test\Request\Action');

        $this->assertSame($request->getRequest(), '\Test\Request\Action');
    }

    public function testSetRequestedRouteMethod()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $request->setMethod('aMethod');

        $this->assertSame($request->getMethod(), 'aMethod');
    }

    public function testSetRequestedRouteParameters()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $request->setParams([
            'abc' => 123
        ]);

        $this->assertSame(['abc' => 123], $request->getParams());
    }

    public function testGetFlashdata()
    {
        $keyStorage = ['aKey' => ['increment' => 1, 'value' => 'hello-world']];
        $_SESSION['__flash_data'] = base64_encode(serialize($keyStorage));

        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $aKey = $request->session->getFlash('aKey');
        $this->assertSame($keyStorage['aKey']['value'], $aKey);
    }

    public function testRemoveRequestSuperglobal()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['hello'] = 'world';

        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $this->assertSame($request->post->fetch('hello'), 'world');
        $request->post->remove('hello');
        $this->assertSame($request->post->fetch('hello'), null);
    }


    public function testFetchingEntireRequestSuperglobalStorage()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['hello'] = 'world';

        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        $request = new Request($dependency, $security);

        $this->assertSame($request->post->fetch(), ['hello' => 'world']);
    }
}
