<?php

namespace core;

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
     * Instantiated load class
     *
     * @var object
     */
    private $load;

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
        //@TODO: validation needs a second look, the required is screwing up on empty (the ol' isset/empty nonsense.. need to validate intent
        //@TODO: setup custom routing based on regex // (can't we get away without using regex tho?)!!!!!!! routesssssss!!!!!!!!
        //@TODO: system vars (_) need to be moved to an array called "system" in the registry, and write protected, _ is lame.

        try {

            $this->load = new Load();

            $configObject = (object) array(
                'database' => $this->load->config('database', 'config'),
                'session'  => $this->load->config('core', 'session'),
                'acl'      => $this->load->config('core', 'acl'),
                'layouts'  => $this->load->config('core', 'layouts'),
                'modules'  => $this->load->config('core', 'modules'),
                'routes'   => $this->load->config('routes', 'override'),
                'helpers'  => $this->load->config('core', 'helpers')
            );

            self::setConfiguration($configObject);

        } catch(\Exception $e) {
            echo "<PRE>";
            print_r($e->getMessage());
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

        $this->router = new Router();

        $this->registerDatabase();
        $this->registerSession();

        $this->request = new Request();

        $this->module = self::$configuration->router->module;
        $this->controller = self::$configuration->router->controller;
        $this->method = self::$configuration->router->method;
        $this->params = self::$configuration->router->params;
        $this->class = '\\app\\modules\\' . self::$configuration->router->module . '\\controllers\\' . ucfirst($this->controller);

    }

    /**
     * @param mixed optional string with reference to config
     * @return mixed bool or config values
     */
    public static function getConfiguration($config = false)
    {
        if($config !== false) {

            if( empty( self::$configuration->$config ) ) {
                return false;
            } else {
                return self::$configuration->$config;
            }

        }

        return self::$configuration;

    }

    /**
     * @param mixed string or object
     * @param bool|object|array optional array of configuration data
     */
    public static function setConfiguration($config, $configObject = false)
    {
        if( !empty( $configObject ) ) {
            self::$configuration->$config = $configObject;
        } else {
            self::$configuration = $config;
        }

    }

    /**
     * Registers the database object
     *
     * @access private
     */
    private function registerDatabase()
    {

        if (self::$configuration->database) {

            App::callEvent('preDatabase');
            $this->database = new Database(self::$configuration->database);
            App::callEvent('postDatabase');

        }

        return;

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

        $moduleExist = file_exists(APP_PATH . '/modules/' . $this->module);
        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, $this->method);
        if (!$moduleExist) {
            $this->output = Router::show404(
                ['errorMessage' => $this->module . ' module could not be found!'],
                self::$configuration->modules->defaultModule
            );
            return false;
        } elseif (!$classExist) {
            $this->output = Router::show404(
                ['errorMessage' => $this->class . ' controller could not be found'],
                $this->module
            );
            return false;
        } elseif (!$methodExist) {
            $this->output = Router::show404(
                ['errorMessage' => $this->method . ' method in the ' . $this->class . ' controller could not be found'],
                $this->module
            );
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
