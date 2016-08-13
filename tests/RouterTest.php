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

        $_SERVER['REQUEST_URI'] = '/example/home/hello/' . $exponent;

        // Re-Instantiate the Router so it overwrites the params with our new URI
        $router = new Router();
        $app    = App::getInstance();

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

        $_SERVER['REQUEST_URI'] = '/example/home/hello/' . $decimal;

        // Re-Instantiate the Router so it overwrites the params with our new URI
        $router = new Router();
        $app    = App::getInstance();

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

        $_SERVER['REQUEST_URI'] = '/example/home/hello/' . $integer;

        // Re-Instantiate the Router so it overwrites the params with our new URI
        $router = new Router();
        $app    = App::getInstance();

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
}
