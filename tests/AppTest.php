<?php
namespace Zewa\Config\Tests;
use Zewa\App;

class AppTest extends \PHPUnit_Framework_TestCase
{
    public function getAppObject()
    {
        $config = new \Zewa\Config();
        $container = new \Zewa\Container();
        $dependency = new \Zewa\Dependency($config, $container);
        return new App($dependency);
    }

    public function testAppWithoutHTTPS()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "off";
        $_SERVER['REQUEST_URI'] = "/say/hello/callback";

        $app = $this->getAppObject();

        $app->initialize();
        $this->assertSame($app->output, 'hello');
    }

    public function testAppCallbackRoute()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['REQUEST_URI'] = "/say/hello/callback";

        $app = $this->getAppObject();

        $app->initialize();
        $this->assertSame($app->output, 'hello');
    }

    public function testAppArrayRoute()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['REQUEST_URI'] = "/say/hello";

        $app = $this->getAppObject();

        $app->initialize();
        $this->assertSame($app->output, 'world');
    }

    /**
     * @expectedException \Exception
     */
    public function testAppInvalidRequest()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['REQUEST_URI'] = "/not/existing";

        $app = $this->getAppObject();

        $app->initialize();
    }

    public function testAppMagicStringify()
    {
        $_SERVER['PHP_SELF'] = "index.php";
        $_SERVER['HTTP_HOST'] = "example.com";
        $_SERVER['HTTPS'] = "on";
        $_SERVER['REQUEST_URI'] = "/say/hello";

        $app = $this->getAppObject();

        $app->initialize();
        $this->assertSame($app->output, (string)$app);
    }
}
