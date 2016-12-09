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

        $request = $this->loadRequestObject();

        $request->delete->set('hello', 'world');

        $this->assertSame($request->delete->fetch('hello'), 'world');
    }

    public function testSetPutRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $request = $this->loadRequestObject();

        $request->put->set('hello', 'world');

        $this->assertSame($request->put->fetch('hello'), 'world');
    }

    public function testSetRequestRouteRequest()
    {
        $request = $this->loadRequestObject();

        $request->setRequest('\Test\Request\Action');

        $this->assertSame($request->getRequest(), '\Test\Request\Action');
    }

    public function testSetRequestedRouteMethod()
    {
        $request = $this->loadRequestObject();

        $request->setMethod('aMethod');

        $this->assertSame($request->getMethod(), 'aMethod');
    }

    public function testSetRequestedRouteParameters()
    {
        $request = $this->loadRequestObject();

        $request->setParams([
            'abc' => 123
        ]);

        $this->assertSame(['abc' => 123], $request->getParams());
    }

    public function testGetFlashdata()
    {
        $keyStorage = ['aKey' => ['increment' => 1, 'value' => 'hello-world']];
        $_SESSION['__flash_data'] = base64_encode(serialize($keyStorage));

        $request = $this->loadRequestObject();

        $aKey = $request->session->getFlash('aKey');
        $this->assertSame($keyStorage['aKey']['value'], $aKey);
    }

    public function testSetFlashdata()
    {
        $request = $this->loadRequestObject();

        $request->session->setFlash('aKey', true);
        $aKey = (bool)$request->session->getFlash('aKey');
        $anEmptyKey = (bool)$request->session->getFlash('aKeyThatDoesntExist');

        $this->assertTrue($aKey);
        $this->assertFalse($anEmptyKey);

        $request->session->setFlash('hello', 'world');
        $this->assertSame('world', $request->session->getFlash('hello'));
    }

    public function testRemoveRequestSuperglobal()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['hello'] = 'world';

        $request = $this->loadRequestObject();

        $this->assertSame($request->post->fetch('hello'), 'world');
        $request->post->remove('hello');
        $this->assertSame($request->post->fetch('hello'), null);
    }


    public function testFetchingEntireRequestSuperglobalStorage()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['hello'] = 'world';

        $request = $this->loadRequestObject();

        $this->assertSame($request->post->fetch(), ['hello' => 'world']);
    }

    public function testSessionStorage()
    {
        $request = $this->loadRequestObject();
        $request->session->set('hello','world');
        $this->assertSame($request->session->fetch('hello'), 'world');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionPurge()
    {
//        $this->testSessionStorage();
        session_start();
        $request = $this->loadRequestObject();
        $request->session->set('hello','world');
        $this->assertSame($request->session->fetch('hello'), 'world');
        $request->session->destroy();
        $this->assertSame($request->session->fetch('hello'), null);
    }

    public function testRedirectWhenHeadersAlreadySent()
    {
        @header('HTTP/1.1 301 Moved Permanently');
        @header('Location: https://example.com/a/friendly/redirect');
        $request = $this->loadRequestObject();

        $this->assertFalse($request->redirect('https://example.com/a/friendly/redirect'));
    }

    /**
     * @runInSeparateProcess
     * @dataProvider statusCodeProvider
     */
    public function testRedirect($statusCode)
    {
        $request = $this->loadRequestObject();
        $request->redirect('https://example.com/a/friendly/redirect', $statusCode);
        $headers = xdebug_get_headers();
        $this->assertSame('Location: https://example.com/a/friendly/redirect', $headers[0]);
    }

    /** dataProvider for statusCodes */
    public function statusCodeProvider()
    {
        return [
            ['301'],
            ['307'],
            ['302'],
        ];
    }
    private function loadRequestObject()
    {
        $config = new Config();
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = $dependency->resolve('\Zewa\Security');
        return new Request($dependency, $security);
    }

}
