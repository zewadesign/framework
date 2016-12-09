<?php
/**
 * Tests for the Request class.
 */
namespace Zewa\Tests;

use Zewa\Config;
use Zewa\Container;
use Zewa\Dependency;
use Zewa\HTTP\Request;
use Zewa\Router;
use Zewa\Security;
use Zewa\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function testRenderView()
    {
        $view = $this->loadViewObject();
        $view->setProperty('aKey', 'is nice');
        $rendered = $view->render('render-test');
        $this->assertSame('hello world', $rendered);
    }

    public function testSetLayoutToNull()
    {
        $view = $this->loadViewObject();
        $view->setLayout('layout');
        $view->setLayout(); // optionally null
        $this->assertSame($view->getLayout(), null);
    }

    private function getCombinedViewAndLayout()
    {
        ob_start();
        require APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'a-page-layout-combined.php';
        $combined = ob_get_contents();
        ob_end_clean();

        return $combined;
    }

    private function loadViewObject()
    {
        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);
        $container = new Container();
        $dependency = new Dependency($config, $container);
        $security = new Security();
        $request = new Request($dependency, $security);
        $router = new Router($config);

        return new View($config, $router, $request, $container);
    }

    public function testAltRenderSyntax()
    {
        $view = $this->loadViewObject();
        $rendered = $view->render('a-page', 'layout');

        $this->assertSame($this->getCombinedViewAndLayout(), $rendered);
    }

    public function testVerboseRenderSyntax()
    {
        $view = $this->loadViewObject();

        $view->setView('a-page');
        $view->setLayout('layout');
        $rendered = $view->render();

        $this->assertSame($this->getCombinedViewAndLayout(), $rendered);
    }

    /**
     * @expectedException \Zewa\Exception\LookupException
     */
    public function testLayoutLookupException()
    {
        $view = $this->loadViewObject();
        $view->render('a-page', 'a-layout-that-doesnt-exist');
    }

    /**
     * @expectedException \Zewa\Exception\LookupException
     */
    public function testViewLookupException()
    {
        $view = $this->loadViewObject();
        $view->render('a-page-that-doesnt-exist');
    }

    public function testPropertyStorage()
    {
        $view = $this->loadViewObject();

        $view->setProperty('hello', 'world');
        $view->setProperty('world', 'hello');
        $view->setProperty('remove', 'me');
        $this->assertSame('world', $view->getProperty('hello'));
        $this->assertSame(['hello' => 'world', 'world' => 'hello', 'remove' => 'me'], $view->getProperty());
        $view->unsetProperty('remove');
        $this->assertSame(['hello' => 'world', 'world' => 'hello'], $view->getProperty());

        $view->setProperty(['world' => 'hello']);

        $this->assertSame('hello', $view->getProperty('world'));
    }

    public function testViewQueuing()
    {
        $view = $this->loadViewObject();

        $view->setView('a-page');
        $view->setView('render-test');
        $aView1 = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'a-page.php';
        $aView2 = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'render-test.php';

        $this->assertSame(['a-page' => $aView1, 'render-test' => $aView2], $view->getView());
        $this->assertSame($aView1, $view->getView('a-page'));
    }

    public function testEmptyResourceQueue()
    {
        $view = $this->loadViewObject();

        $js = '<script>baseURL = \'https://example.com/\'</script>' . "\r\n";
        $this->assertSame($view->fetchCSS(), '');
        $this->assertSame($view->fetchJS(), $js);
    }

    public function testResourceQueue()
    {

        $view = $this->loadViewObject();

        $view->addCSS(['a-path-to.css', 'a-path-to-2.css']);

        $view->addJS(['a-path-to.js', 'a-path-to-2.js']);

        $css = '<link rel="stylesheet" href="a-path-to.css">' . "\r\n"
            . '<link rel="stylesheet" href="a-path-to-2.css">' . "\r\n";

        $js = '<script>baseURL = \'https://example.com/\'</script>' . "\r\n"
            . '<script src="a-path-to.js"></script>' . "\r\n"
            . '<script src="a-path-to-2.js"></script>' . "\r\n";
//
        $this->assertSame($view->fetchCSS(), $css);
        $this->assertSame($view->fetchJS(), $js);
    }

    public function testRendering404()
    {
        ob_start();
        require APP_PATH . DIRECTORY_SEPARATOR . 'Layouts' . DIRECTORY_SEPARATOR . '404.php';
        $fixture = ob_get_contents();
        ob_end_clean();
        $view = $this->loadViewObject();
        $this->assertSame(@$view->render404(), $fixture);
    }
}