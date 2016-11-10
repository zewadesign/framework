<?php

namespace Zewa;

//use Zewa\Interfaces\ContainerInterface;

/**
 * This class is the starting point for application
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class App
{
    /**
     * Events
     */
    private static $events;

    /**
     * Return value from application
     *
     * @var string
     */
    private $output = false;

    /**
     * Namespaced controller path
     *
     * @var string
     */
    private $class;

    /**
     * Instantiated class object
     *
     * @var Controller
     */
    private $instantiatedClass;

    /**
     * Module being accessed
     *
     * @var string
     */
    private $module;

    /**
     * Controller being accessed
     *
     * @var string
     */
    private $controller;

    /**
     * Method being accessed
     *
     * @var string
     */
    private $method;

    /**
     * Params being passed
     *
     * @var array
     */
    private $params;

    /**
     * @var DIContainer $container
     */
    private $container;

    /**
     * Application bootstrap process
     *
     * The application registers the configuration in the app/config/core.php
     * and then processes, and makes available the configured resources
     *
     * App constructor.
     * @param DIContainer $container
     */
    public function __construct(DIContainer $container)
    {
        $this->configuration = $container->resolve('\Zewa\Config');
        $this->container = $container;

        $this->router = $container->resolve('\Zewa\Router', true);
        $this->request = $container->resolve('\Zewa\Request', true);
        $this->view = $container->resolve('\Zewa\View');

        $this->prepare();
    }

    /**
     * Calls the proper shell for app execution
     *
     * @access private
     */
    public function initialize()
    {
        $this->start();
        return $this;
    }

    /**
     * App preparation cycle
     */
    private function prepare()
    {
        $routerConfig = $this->configuration->get('Routing');

        $this->module = ucfirst($routerConfig->module);
        $this->controller = ucfirst($routerConfig->controller);
        $this->method = $routerConfig->method;
        $this->params = $routerConfig->params;
        $this->class = 'Zewa\\App\\Modules\\' . $this->module . '\\Controllers\\' . ucfirst($this->controller);
    }

//    public function setContainer(Container $container)
//    {
//        $this->container = $container;
//    }

    /**
     * Verifies the provided application request is a valid request
     *
     * @access private
     */
    private function validateRequest()
    {
        //catch exception and handle
        try {
            $class = new \ReflectionClass($this->class);
            $class->getMethod($this->method);
        } catch (\ReflectionException $e) {
            $view = $this->container->resolve('\Zewa\View');
            $this->output = $view->render404(['Invalid method requests']); //Router::show404(
            return false;
        }

        return true;
    }

    /**
     * Processes the application request
     *
     * @access private
     */
    private function start()
    {
        if ($this->validateRequest() === false) {
            return false;
        }

        App::callEvent('preController');
        $this->instantiatedClass = $this->container->resolve($this->class);
        App::callEvent('postController');

        $this->instantiatedClass->setConfig($this->configuration);
        $this->instantiatedClass->setRouter($this->router);
        $this->instantiatedClass->setRequest($this->request);
        $this->instantiatedClass->setContainer($this->container);
        $this->instantiatedClass->setView($this->view);

        $this->output = call_user_func_array(
            [&$this->instantiatedClass, $this->method],
            $this->params
        );
    }

    /**
     * Attach (or remove) multiple callbacks to an event and trigger those callbacks when that event is called.
     *
     * @param string $event    name
     * @param mixed  $value    the optional value to pass to each callback
     * @param mixed  $callback the method or function to call - FALSE to remove all callbacks for event
     */

    public static function addEvent($event, $callback = false)
    {
        // Adding or removing a callback?
        if ($callback !== false) {
            self::$events[$event][] = $callback;
        } else {
            unset(self::$events[$event]);
        }
    }

    public function callEvent($event, $method = false, $arguments = [])
    {
        if (isset(self::$events[$event])) {
            foreach (self::$events[$event] as $e) {
                if ($method !== false) { // class w/ method specified
                    $object = new $e();
                    $value = call_user_func_array(
                        [&$object, $method],
                        $arguments
                    );
                } else {
                    if (class_exists($e)) {
                        $value = new $e($arguments); // class w/o method specified
                    } else {
                        $value = call_user_func($e, $arguments); // function yuk
                    }
                }
            }

            return $value;
        }
    }


    /**
     * Prepare application return value into a string
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        if (!$this->output) {
            $this->output = '';
        }

        App::callEvent('postApplication');

        return $this->output;
    }
}
