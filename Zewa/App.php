<?php

namespace Zewa;

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
     * System configuration
     *
     * @var object
     */
    private static $configuration;

    /**
     * System service management
     *
     * @var object
     */
    private static $services;

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
        //@TODO: unset unnececessary vars/profile/unit testing..? how?
        //@TODO: better try/catch usage
        //@TODO: setup custom routing based on regex // (can't we get away without using regex tho?)!!!!!!! routesssssss!!!!!!!!
        try {

            self::$configuration = new \stdClass();
            self::setConfiguration();

        } catch(\RuntimeException $e) {
            echo "<strong>RuntimeException:</strong> <br/>";
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * Calls the proper shell for app execution
     * @access private
     */
    public function initialize() {

        $this->prepare();

        if (self::$configuration->acl) {
            $acl = new ACL(
                $this->request->session('uid'),
                $this->request->session('role_id')
            );
            $acl->secureStart(function(){
                return $this->start();
            });
        } else {
            $this->start();
        }

        return $this;

    }

    /**
     * App preparation cycle
     */
    private function prepare()
    {
        App::callEvent('preApplication');

        $this->registerSession();

        self::$services = new ServiceManager();

        $this->router = App::getService('router');
        $this->request = App::getService('request');
        $this->database = App::getService('database');

        $this->module = ucfirst(self::$configuration->router->module);
        $this->controller = ucfirst(self::$configuration->router->controller);
        $this->method = self::$configuration->router->method;
        $this->params = self::$configuration->router->params;
        $this->class = '\\App\\Modules\\' . self::$configuration->router->module . '\\Controllers\\' . ucfirst($this->controller);
    }

    public static function getService($service = null, $new = false, $options = []) {
        if ($service !== null) {
            if($new === false ) {
                return self::$services->$service;
            } else if($new === true || empty ( self::$services->$service ) ) {
                self::$services->$service = call_user_func_array(self::$services->$service, $options);
                return self::$services->$service;
            }
        }
    }

    /**
     * @param mixed string with reference to config
     * @return mixed bool or config values
     */
    public static function getConfiguration($config = null)
    {
        if($config !== null) {
            if( ! empty ( self::$configuration->$config ) ) {
                return self::$configuration->$config;
            }

            return false;
        }

        return self::$configuration;

    }

    /**
     * @param $config mixed null|string
     * @param null|object|array optional array of configuration data
     *
     * @return bool
     * @throws StateException
     */
    public static function setConfiguration($config = null, $configObject = null)
    {
        try {
            if( $config !== null && $configObject !== null && !empty( $configObject ) ) {
                self::$configuration->$config = $configObject;
                return true;
            } else if($config === null && $configObject === null) {

                $files = glob(APP_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . '*.php');
                foreach ($files as $index => $filename){
                    $pieces = explode('/', $filename);
                    $file = $pieces[count($pieces) - 1];
                    $fileProperties = explode('.', $file);

                    $vars = include($filename);
                    if($vars === 1) {
                        throw new Exception\StateException('No configuration values found in: ' . $fileProperties[0]);
                    }

                    if($fileProperties[0] === 'services') {
                        self::$configuration->$fileProperties[0] = $vars;
                    } else {
                        self::$configuration->$fileProperties[0] = json_decode(json_encode($vars));
                    }
                }

                return true;
            }

            throw new Exception\StateException('You must provide the configuration key, and its value.');
        } catch(Exception\StateException $e) {
            echo "<strong>StateException:</strong> <br/>";
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * Registers the session object
     *
     * @access private
     */
    private function registerSession()
    {

        $config = self::$configuration->session;

        if($config !== false) {
            App::callEvent('preSession');
            new SessionHandler(
                $config->interface, $config->securityCode, $config->expiration,
                $config->lockToUserAgent, $config->lockToIP, $config->gcProbability,
                $config->gcDivisor, $config->tableName
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
     * @param string $event name
     * @param mixed $value the optional value to pass to each callback
     * @param mixed $callback the method or function to call - FALSE to remove all callbacks for event
     */

    public static function addEvent($event, $callback = false)
    {
        // Adding or removing a callback?
        if($callback !== false){
            self::$events[$event][] = $callback;
        } else {
            unset(self::$events[$event]);
        }

    }

    public function callEvent($event, $method = false, $arguments = [])
    {
        if(isset(self::$events[$event])) {
            foreach (self::$events[$event] as $e) {

                if($method !== false) { // class w/ method specified
                    $object = new $e();
                    $value = call_user_func_array(
                        [&$object, $method],
                        $arguments
                    );
                } else {
                    if(class_exists($e)) {
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
