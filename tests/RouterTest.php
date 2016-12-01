<?php

namespace Zewa\Tests;

use \Zewa\Config;
use \Zewa\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
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

        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);
        $router = new Router($config);

        $detectedRouteParams = $router->getParameters();
        $this->assertSame($routeParam, $detectedRouteParams[0]);
    }

    /**
     * If you pass an exponent with a + such as +1.3e3
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

        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);
        $router = new Router($config);
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
}
