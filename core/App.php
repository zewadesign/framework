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
     * Instiated hook class
     *
     * @var object
     */
    private $hook;

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

        $this->load = new Load();

        $configObject = (object) array(
            'database' => $this->load->config('core', 'database'),
            'session'  => $this->load->config('core', 'session'),
            'cache'    => $this->load->config('core', 'cache'),
            'acl'      => $this->load->config('core', 'acl'),
            'layouts'  => $this->load->config('core', 'layouts'),
            'modules'  => $this->load->config('core', 'modules'),
            'routes'   => $this->load->config('routes', 'override'),
            'hooks'    => $this->load->config('core', 'hooks'),
            'helpers'  => $this->load->config('core', 'helpers')
        );

        self::setConfiguration($configObject);
        try {
            $this->initialize();
        } catch(\Exception $e) {
            echo "<PRE>";
            print_r($e->getMessage());
        }
    }

    /**
     * Calls the proper shell for app execution
     * @access private
     */
    private function initialize() {

        $this->prepare();

        if (self::$configuration->acl) {

            $acl = new \app\classes\ACL(
                $this->request->session('userId'),
                $this->request->session('roleId')
            );
            $acl->secureStart(function(){
                return $this->start();
            });
        } else {
            $this->start();
        }

    }

    /**
     * App preparation cycle
     */
    private function prepare()
    {

//        if(self::$configuration->hooks !== false) {
        $this->hook = new \app\classes\Hook();
//        }

        $this->hook->call('preApplication');

        $this->router = new Router();
        $this->request = new Request();

        $this->registerDatabase();

        if(self::$configuration->cache !== false) {
            $this->cache = new \app\classes\Cache(
                self::$configuration->cache->host,
                self::$configuration->cache->port
            );
        }

        $this->registerSession();

        $this->module = self::$configuration->router->module;
        $this->controller = self::$configuration->router->controller;
        $this->method = self::$configuration->router->method;
        $this->params = self::$configuration->router->params;
        $this->registerHelpers();
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
     * Registers autoloaded helpers
     *
     * @access private
     */
    private function registerHelpers()
    {
        if ($helpers = self::$configuration->helpers) {
            foreach ($helpers as $type => $component) {
                $this->load->helper($component);
            }
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

            $this->hook->call('preDatabase');
            $this->database = new Database(self::$configuration->database->default);
            $this->hook->call('postDatabase');

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
            $this->hook->call('preSession');
            new SessionHandler(
                $config->interface, $config->securityCode, $config->expiration,
                $config->lockToUserAgent, $config->lockToIP, $config->gcProbability,
                $config->gcDivisor, $config->tableName
            );
            $this->hook->call('postSession');
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

        $this->hook->call('preController');
        $this->instantiatedClass = new $this->class();
        $this->hook->call('postController');

        $this->output = call_user_func_array(
            array(&$this->instantiatedClass, $this->method),
            $this->params
        );
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

        $this->hook->call('postApplication');

        return $this->output;
    }
}
