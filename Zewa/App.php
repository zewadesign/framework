<?php

namespace Zewa;

use Zewa\Exception\LookupException;

/**
 * This class is the starting point for application
 *
 * <code>
 *
 * $out = new core\App();
 * print $out;
 *
 * </code>
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class App
{
    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $instance = false;

    /**
     * Available configuration files
     *
     * @var object
     */
    private $files;

    /**
     * System configuration
     *
     * @var object
     */
    public $configuration;

    /**
     * System service management
     *
     * @var object
     */
    private $services;

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
     * @var string
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
     * Instantiated router class
     *
     * @var object
     */
    private $router;

    /**
     * Instantiated request class
     *
     * @var object
     */
    private $request;

    /**
     * Application bootstrap process
     *
     * The application registers the configuration in the app/config/core.php
     * and then processes, and makes available the configured resources
     */
    public function __construct()
    {
        self::$instance = $this;
        //@TODO: unset unnececessary vars/profile/unit testing..? how?
        //@TODO: better try/catch usage
        //@TODO: setup custom routing based on regex
        // (can't we get away without using regex tho?)!!!!!!! routesssssss!!!!!!!!
        $files = (object)glob(APP_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . '*.php');
        $oFiles = [];

        foreach ($files as $index => $filename) {
            $pieces = explode('/', $filename);
            $file = $pieces[count($pieces) - 1];
            $fileProperties = explode('.', $file);
            $currentFile = $fileProperties[0];
            $oFiles[$currentFile] = $filename;
        }

        $this->files = $oFiles;
        $this->configuration = new \stdClass();

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
        App::callEvent('preApplication');

        $this->registerSession();

        $this->router = new Router();
        $this->request = new Request();
        $this->database = new Database();

        $this->setService('router', $this->router);
        $this->setService('request', $this->request);
        $this->setService('database', $this->database);

        $this->module = ucfirst($this->configuration->router->module);
        $this->controller = ucfirst($this->configuration->router->controller);
        $this->method = $this->configuration->router->method;
        $this->params = $this->configuration->router->params;
        $this->class = '\\App\\Modules\\' . $this->module . '\\Controllers\\' . ucfirst($this->controller);
    }

    private function prepareServices()
    {
        if (isset($this->files['services'])) {
            $services = include $this->files['services'];
            if ($services === false) {
                $this->services = [];
            } else {
                $this->services = (object)$services;
            }
        } else {
            throw new LookupException('No service configuration found.');
        }
    }

    public function getService($service, $new = false, $options = [])
    {
        if ($this->services === null) {
            $this->prepareServices();
        }

        if ($new === false) {
            return $this->services->$service;
        } elseif ($new === true || empty($this->services->$service)) {
            $this->services->$service = call_user_func_array($this->services->$service, $options);
            return $this->services->$service;
        }
    }

    public function setService($service, $class)
    {
        if ($this->services === null) {
            $this->prepareServices();
        }

        $this->services->$service = $class;
    }

    /**
     * @param mixed string with reference to config
     * @return mixed bool or config values
     */
    public function getConfiguration($config = null)
    {
        if ($config !== null) {
            if (! empty($this->configuration->$config)) {
                return $this->configuration->$config;
            } elseif (! empty($this->files{$config})) {
                $vars = include $this->files{$config};

                if ($vars === false) {
                    $this->configuration->{$config} = false;
                } else {
                    //turn array into object the dirty way?
                    $this->configuration->{$config} = json_decode(json_encode($vars));
                }

                return $this->configuration->$config;
            }

            return false;
        }

        return $this->configuration;
    }

    /**
     * @param $config mixed null|string
     * @param null|object|array optional array of configuration data
     *
     * @return bool
     * @throws Exception\StateException
     */
    public function setConfiguration($config = null, $configObject = null)
    {
        if ($config !== null && $configObject !== null && !empty($configObject)) {
            $this->configuration->$config = $configObject;
            return true;
        }

        throw new Exception\StateException('You must provide the configuration key, and its value.');
    }

    /**
     * Registers the session object
     *
     * @access private
     */
    private function registerSession()
    {
        $session = $this->getConfiguration('session');

        if ($session !== false) {
            App::callEvent('preSession');
            new SessionHandler(
                $session->interface,
                $session->securityCode,
                $session->expiration,
                $session->domain,
                $session->lockToUserAgent,
                $session->lockToIP,
                $session->gcProbability,
                $session->gcDivisor,
                $session->tableName
            );
            App::callEvent('postSession');
        }

        return;
    }

    /**
     * Verifies the provided application request is a valid request
     *
     * @access private
     */
    private function processRequest()
    {
        $moduleExist = file_exists(APP_PATH . '/Modules/' . $this->module);
        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, $this->method);

        if (!$moduleExist || !$classExist || !$methodExist) {
            $view = new View();
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
        if (!$this->processRequest()) {
            return false;
        }

        App::callEvent('preController');
        $this->instantiatedClass = new $this->class();
        App::callEvent('postController');

        $this->output = call_user_func_array(
            array(&$this->instantiatedClass, $this->method),
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


    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            return false;
        }

        return self::$instance;
    }
}
