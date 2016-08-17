<?php


namespace Zewa\Tests;

use \Zewa\App;
use \Zewa\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * This tests to ensure that all strings that PHP would parse
     * in to a float given it's exponent notation are NOT converted
     * to float by the Router's normalize method.
     *
     * http://php.net/manual/en/function.is-numeric.php
     * - Up until PHP 7.0.0 Hexidecimal values are parsed as integers.
     *
     * @dataProvider exponentProvider
     */
    public function testRouteExponentParamAsString($exponent)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/example/home/hello' . '/' . $exponent;

        // Create Instance of App
        $app = new \Zewa\App();

        $routerConfig    = $app->getConfiguration('router');

        $firstRouteParam = $routerConfig->params[0];
        $this->assertTrue(is_string($firstRouteParam));
        $this->assertTrue(!is_float($firstRouteParam));
    }

    /**
     * If you pass an exponent with a + such as +1.3e3
     */
    public function exponentProvider()
    {
        return [
            ['9E26'],
            ['123e1'],
            ['-1.3e3'],
            ['2.1e-5'],
            ['0x539'],
        ];
    }

    /**
     * This tests to ensure that decimal values passed to the route as a param
     * are parsed and returned as decimals (as opposed to strings)
     *
     * @dataProvider decimalProvider
     */
    public function testRouteDecimalParamAsFloat($decimal)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/example/home/hello' . '/' . $decimal;

        // Create Instance of App
        $app = new \Zewa\App();

        $routerConfig    = $app->getConfiguration('router');

        $firstRouteParam = $routerConfig->params[0];

        $this->assertTrue(is_float($firstRouteParam));
        $this->assertTrue(!is_string($firstRouteParam));
    }

    public function decimalProvider()
    {
        return [
            ['9.9999'],
            ['123.0932'],
            ['0.1'],
            ['99912.4044'],
        ];
    }

    /**
     * This tests to ensure that integer values passed to the route as a param
     * are parsed and returned as integers (as opposed to strings)
     *
     * @dataProvider integerProvider
     */
    public function testRouteIntegerParamAsInteger($integer)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/example/home/hello' . '/' . $integer;

        // Create Instance of App
        $app = new \Zewa\App();

        $routerConfig    = $app->getConfiguration('router');
        $firstRouteParam = $routerConfig->params[0];

        $this->assertTrue(is_int($firstRouteParam));
        $this->assertTrue(!is_string($firstRouteParam));
    }

    public function integerProvider()
    {
        return [
            ['1'],
            [99999999999999999],
            ['792643']
        ];
    }

    /**
     * Test passing some bad route params to the router.
     *
     * @dataProvider badRouteParamProvider
     * @expectedException \Zewa\Exception\RouteException
     */
    public function testBadRouteParam($badRouteParam)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/example/home/hello' . '/' . $badRouteParam;

        // Create Instance of App
        $app = new \Zewa\App();

    }

    public function badRouteParamProvider()
    {
        return [
            ['%'],
            ['@'],
            ['^'],
            ['*'],
            ['!'],
            ['~'],
            ['`'],
            ['+'],
            ['|'],
            ['a__']
        ];
    }

    public function testNormalizeURIFromPathInfo()
    {
        global $_SERVER;
        // Normalize URI from Path Info superglobal.

        $app = new \Zewa\App();
        $routerConfig = $app->getConfiguration('router');

        $this->assertSame('Example/Home/Index',$routerConfig->uri);
    }

    /**
     * Test the result of normalizing empty routes which should result in
     * the URI being generated out of the default module, controller and method.
     *
     * @dataProvider emptyURIProvider
     */
    public function testNormalizeEmptyURI($emptyURI)
    {
        global $_SERVER;

        // Create Instance of App
        $app = new \Zewa\App();

        if(!empty($_SERVER['PATH_INFO'])) {
            unset($_SERVER['PATH_INFO']);
        }

        $_SERVER['REQUEST_URI'] = $emptyURI;

        $router = new Router();
        $app = App::getInstance();
        $routerConfig    = $app->getConfiguration('router');

        $uriShouldBe = $routerConfig->module . "/" . $routerConfig->controller . "/" . $routerConfig->method;

        $this->assertSame($router->uri,$uriShouldBe);
    }

    public function emptyURIProvider()
    {
        return [
            ['/'],
            [''],
        ];
    }

    public function testDiscoverRoute()
    {
        global $_SERVER;
        // Create Instance of App
        $app = new \Zewa\App();
        $routerConfig  = $app->getConfiguration('router');
        $this->assertSame($routerConfig->method,'Index');
    }

    public function testCurrentURL()
    {
        global $_SERVER;

        $_SERVER['HTTP_HOST'] = 'test.zewa.com';
        $_SERVER['REQUEST_URI'] = '/batman';

        // Create Instance of App
        $app = new \Zewa\App();
        $router = $app->getService('router');
        $currentURL = $router->currentURL();

        $this->assertSame('http://test.zewa.com/Batman/Home/Index',$currentURL);

        $currentURLWithParams = $router->currentURL(['param1' => 'something','p2' => 'nothing']);
        $this->assertSame(
            'http://test.zewa.com/Batman/Home/Index?param1=something&p2=nothing',
            $currentURLWithParams
        );
    }

    public function testCurrentURLWithHTTPS()
    {
        global $_SERVER;

        $_SERVER['HTTP_HOST'] = 'test.zewa.com';
        $_SERVER['REQUEST_URI'] = '/batman';
        $_SERVER['HTTPS'] = "on";

        // Create Instance of App
        $app = new \Zewa\App();

        $router = $app->getService('router');
        $currentURL = $router->currentURL();

        $this->assertSame('https://test.zewa.com/Batman/Home/Index',$currentURL);

        $currentURLWithParams = $router->currentURL(['param1' => 'something']);
        $this->assertSame(
            'https://test.zewa.com/Batman/Home/Index?param1=something',
            $currentURLWithParams
        );
    }
}
