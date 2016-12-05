<?php
/**
 * Router test
 *
 * We can't test the redirect method since it uses header() function.
 */
namespace Zewa\Tests;

use \Zewa\Config;
use \Zewa\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The honest truth is I don't really know why this is useful (~jhoughtelin)
     * but it returns the value part of the configured route so this ties
     * directly the configured routes
     */
    public function testGetAction()
    {
        $_SERVER['REQUEST_URI'] = '/hello/batman';

        $router = $this->getNewRouterObject();

        $this->assertSame('example/home/hello/$1', $router->getAction());
    }

    /**
     * This tests to ensure that router params are parsed from the configured routes.
     * The only configured route is: '/hello/([A-Za-z0-9]+)'
     * so any alphanumeric param should be parsed properly.
     *
     * @dataProvider routeParamProvider
     */
    public function testRouteParamAsString($routeParam)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/hello/' . $routeParam;

        $router = $this->getNewRouterObject();

        $detectedRouteParams = $router->getParameters();
        $this->assertSame($routeParam, $detectedRouteParams[0]);
    }

    /**
     * Simple alphanumeric router paramater provider
     */
    public function routeParamProvider()
    {
        return [
            ['9E26'],
            ['123e1'],
            ['0x539'],
            ['0'],
            ['ABCEDFG'],
            ['ABC123']
        ];
    }

    /**
     * Test passing some bad route params to the router.
     * These are considered safe params: a-z, 0-9, :, _, [, ], +
     * Everything else should throw a RouteException
     *
     * @dataProvider badRouteParamProvider
     * @expectedException \Zewa\Exception\RouteException
     */
    public function testBadRouteParam($badRouteParam)
    {
        global $_SERVER;

        $_SERVER['REQUEST_URI'] = '/hello/' . $badRouteParam;

        $router = $this->getNewRouterObject();
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
            ['+'], // The test should NOT pass with this since the docs say this is a safe param.
            ['|']
        ];
    }

    public function testGetUriFromPathInfo()
    {
        global $_SERVER;

        if (isset($_SERVER['REQUEST_URI'])) {
            /**
             * I shouldn't have to wrap this in an if statement but sometimes
             * this is set from a previous test due to not unset()ing them after
             * each test. =\
             */
            unset($_SERVER['REQUEST_URI']);
        }

        $_SERVER['PATH_INFO'] = "/hello/batman";

        $router = $this->getNewRouterObject();

        $this->assertSame('hello/batman', $router->uri);

        unset($_SERVER['PATH_INFO']);
    }

    public function testNoURIEqualsBlankUri()
    {
        global $_SERVER;
        $_SERVER['REQUEST_URI'] = "/";

        $router = $this->getNewRouterObject();

        $this->assertTrue(empty($router->uri));

        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * This test SHOULD work.. but it doesn't.
     *
     * It fails because the number 1 gets appended to the end of the current URL
     * when no query string is present.
     */
    public function testGetCurrentHTTPSURL()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['QUERY_STRING'] = "";
        $_SERVER['REQUEST_URI'] = "/hello/batman";

        $router = $this->getNewRouterObject();
        $this->assertSame('https://example.com/hello/batman', $router->currentURL());
    }

    public function testGetCurrentHTTPSURLWithQueryString()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['QUERY_STRING'] = "Gotham=City";
        $_SERVER['REQUEST_URI'] = "/hello/batman";

        $router = $this->getNewRouterObject();
        $this->assertSame('https://example.com/hello/batman?Gotham=City', $router->currentURL());
    }

    public function testQueryStringAddition()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['QUERY_STRING'] = "Gotham=City";
        $_SERVER['REQUEST_URI'] = "/hello/batman";

        $router = $this->getNewRouterObject();
        $urlWithQueryStringAdded = $router->addQueryString($router->baseURL('hello/batman'), 'Gotham', 'City');
        $this->assertSame('https://example.com/hello/batman?Gotham=City', $urlWithQueryStringAdded);

        $urlWithQueryStringAddedTwice = $router->addQueryString($urlWithQueryStringAdded, 'Joker', 'Wins');
        $this->assertSame('https://example.com/hello/batman?Gotham=City&Joker=Wins',$urlWithQueryStringAddedTwice);
    }

    public function testQueryStringRemoval()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['REQUEST_URI'] = "/hello/batman";

        $router = $this->getNewRouterObject();
        $urlWithQueryStringAdded = $router->addQueryString($router->baseURL('hello/batman'), 'Gotham', 'City');
        $urlWithQueryStringRemoved = $router->removeQueryString($urlWithQueryStringAdded, 'Gotham');

        $this->assertSame('https://example.com/hello/batman', $urlWithQueryStringRemoved);
    }

    /**
     * When REQUEST_URI AND PATH_INFO don't exist.
     * The URI is empty.
     */
    public function testNoURISourceEqualsBlankUri()
    {
// Make sure the only two sources for URI data are non-existent.
        if (isset($_SERVER['REQUEST_URI'])) {
            unset($_SERVER['REQUEST_URI']);
        }

        if (isset($_SERVER['PATH_INFO'])) {
            unset($_SERVER['PATH_INFO']);
        }

        $router = $this->getNewRouterObject();

        $this->assertTrue(empty($router->uri));
    }

    private function getNewRouterObject()
    {
        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);

        return new Router($config);
    }
}
