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
        try {
            // The whole app seems to rely on this global Registry...
            $this->load = new Load();

            $configObject = (object) array(
                'database' => $this->load->config('core', 'database'),
                'session'  => $this->load->config('core', 'session'),
                'cache'    => $this->load->config('core', 'cache'),
                'acl'      => $this->load->config('core', 'acl'),
                'modules'  => $this->load->config('core', 'modules'),
                'routes'   => $this->load->config('routes', 'override'),
                'hooks'    => $this->load->config('core', 'hooks'),
                'autoload' => $this->load->config('core', 'autoload')
            );

            self::setConfiguration($configObject);

            $this->prepare();
            $this->initialize();

        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * App preparation cycle
     */
    private function prepare()
    {

        if(self::$configuration->hooks !== false) {
            $this->hook = new \app\libraries\Hook();
        }

        $this->hook->call('preApplication');

        new Router();

        $this->module = self::$configuration->router->module;
        $this->controller = self::$configuration->router->controller;
        $this->method = self::$configuration->router->method;
        $this->params = self::$configuration->router->params;

        $this->initializeDependencies();
        $this->autoload();
        $this->class = '\\app\\modules\\' . self::$configuration->router->module . '\\controllers\\' . ucfirst($this->controller);

    }

    /**
     * Calls the proper shell for app execution
     * @access private
     */
    private function initialize() {

        if (self::$configuration->acl) {
            $this->secureStart();
        } else {
            $this->start();
        }

    }

    /**
     * Initiates the dependencies
     *
     * @access private
     */
    private function initializeDependencies()
    {

        if (self::$configuration->database) {
            $this->hook->call('preDatabase');
            $this->register('database');
            $this->hook->call('postDatabase');
        }
        if (self::$configuration->cache) {
            $this->hook->call('preCache');
            $this->register('cache');
            $this->hook->call('postCache');
        }
        if (self::$configuration->session) {
            $this->hook->call('preSession');
            $this->register('session');
            $this->hook->call('postSession');
        }
        if (self::$configuration->acl) {
            $this->hook->call('preACL');
            $this->register('acl');
            $this->hook->call('postACL');
        }

    }

    /**
     * @param mixed optional string with reference to config
     * @return mixed bool or config values
     */
    public static function getConfiguration($config = false)
    {

        if($config !== false) {

            if( empty( self::$configuration[$config] ) ) {
                return false;
            } else {
                return self::$configuration[$config];
            }

        }

        return self::$configuration;

    }

    /**
     * @param mixed string or object
     * @param mixed optional array of configuration data
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
     * Autoloads configured resources
     *
     * @access private
     */
    private function autoload()
    {
        if ($autoload = self::$configuration->autoload) {
            foreach ($autoload as $type => $component) {
                foreach ($component as $comp) {
                    switch ($type) {
                        case 'helpers':
                            $this->load->helper($comp);
                            break;
                        case 'libraries':
                            foreach ($comp as $lib => $args) {
                                $this->load->library($lib, $args);
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * Registers core classes
     *
     * @access private
     *
     * @param string $resource
     */
    private function register($resource)
    {

        switch ($resource) {
            case 'database':
                $this->registerDatabase();
                break;
            case 'cache':
                $this->registerCache();
                break;
            case 'session':
                $this->registerSession();
                break;
            case 'acl':
                $this->registerACL();
                break;
        }

    }

    /**
     * Registers the database object
     *
     * @access private
     */
    private function registerDatabase()
    {

        Registry::add('_database', new Database(
            'default', // you can name your db, for switching between..
            self::$configuration->database['default']
        ));

    }

    /**
     * Registers the cache object
     *
     * @access private
     */
    private function registerCache()
    {
        $memcached = new \Memcached();
        $memcached->addServer(self::$configuration->cache->host, self::$configuration->cache->port);
        Registry::add('_memcached', $memcached);
    }

    /**
     * Registers the session object
     *
     * @access private
     */
    private function registerSession()
    {

        if (!self::$configuration->session['database']) {
            throw new \Exception('Not supported yet..');
        } else {
            new SessionHandler(Registry::get('_database'), 'securitycode', 7200, true, false, 1, 100);
        }

    }

    /**
     * Registers the ACL object
     *
     * @access private
     */
    private function registerACL()
    {

        Registry::add('_acl', self::$configuration->acl);

    }

    /**
     * Verifies the provided application request is a valid request
     *
     * @access private
     */
    private function verifyApplicationRequest()
    {

        $moduleExist = file_exists(APP_PATH . '/modules/' . $this->module);
        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, $this->method);

        if (!$moduleExist) {
            $this->output = Router::show404(self::$configuration->modules['defaultModule'] . '/404');
            return false;
        } elseif (!$classExist) {
            $this->output = Router::show404($this->module . '/404');
            return false;
        } elseif (!$methodExist) {
            $this->output = Router::show404($this->module . '/404');
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
        if (!$this->verifyApplicationRequest()) {
            return false;
        }

        $this->hook->call('preController');
        $this->instantiatedClass = new $this->class();
        /*
        $this->instantiatedClass->setRouter(Registry::get('_router'));
        $this->instantiatedClass->setLoad(Registry::get('_load'));
        $this->instantiatedClass->setRequest(Registry::get('_request'));
        $this->instantiatedClass->setOutput(Registry::get('_output'));
        $this->instantiatedClass->setValidate(Registry::get('_validate'));
        */
        $this->hook->call('postController');

        $this->output = call_user_func_array(
            array(&$this->instantiatedClass, $this->method),
            $this->params
        );
    }

    /**
     * Handles client request within  ACL
     *
     * @access private
     */
    public function secureStart()
    {

        $request = Registry::get('_request');

        $userId = $request['uid'];
        $roleId = $request['roleId'];

        $ACL = new Acl($userId, $roleId);

        $authorizationCode = $ACL->hasAccessRights($this->module, $this->controller, $this->method);

        //@TODO:store access rights in registry for dynamic menu display
        switch ($authorizationCode) {

            case '1':
                $this->start();
                break;
            case '2':
                $this->secureRedirect();
                break;

            case '3': //@TODO: setup module 404's.
                $this->output = $this->noAccessRedirect();
                break;
        }
    }

    /**
     * Redirect if guest and access is insufficient / protected
     *
     * @access private
     */
    private function secureRedirect()
    {

        Registry::get('_request')->setFlashdata('alert', (object) array('info' => 'Please login to continue!'));

        $currentURL = currentURL();
        $currentURL = str_replace(baseURL(), '', $currentURL);
        $currentURL = base64_encode($currentURL);

        $redirect = $this->load->config('core', 'modules')[$this->module]['aclRedirect'];

        Router::redirect(baseURL($redirect . '?r=' . $currentURL));

    }

    /**
     * Set 401 header, provide no access view if authenticated
     * and access is insufficient / protected
     *
     * @access private
     */
    private function noAccessRedirect()
    {

        return Router::showNoAccess($this->module . '/noaccess');

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
