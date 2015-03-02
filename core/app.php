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

Class App
{

    /**
     * System configuration
     *
     * @var object
     */
    private $_configuration;

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
     * Instiated hook class
     *
     * @var object
     */

    private $hooks;

    /**
     * Application bootstrap process
     *
     * The application registers the configuration in the app/config/core.php
     * and then processes, and makes available the configured resources
     */

    public function __construct() {
        //@TODO: unset unnececessary vars/profile/unit testing..? how?
        //@TODO: better try/catch usage
        //@TODO: validation needs a second look, the required is screwing up on empty (the ol' isset/empty nonsense.. need to validate intent
        //@TODO: setup custom routing based on regex // (can't we get away without using regex tho?)!!!!!!! routesssssss!!!!!!!!
        //@TODO: system vars (_) need to be moved to an array called "system" in the registry, and write protected, _ is lame.
        try {

            Registry::add('_load', new Load());
            $this->load = Registry::get('_load');
            $this->_configuration = (object) array(
                'database' => $this->load->config('core', 'database'),
                'session' => $this->load->config('core', 'session'),
                'cache' => $this->load->config('core','cache'),
                'acl' => $this->load->config('core','acl'),
                'modules' => $this->load->config('core','modules'),
                'hooks' => $this->load->config('core','hooks')
            );

            Registry::add('_configuration',$this->_configuration);

            $this->hook = new Hook();

            $this->hook->dispatch('preApplication');

            $this->prepareApplication();

            $this->autoload();

            $this->class = 'app\\modules\\'.Registry::get('_module').'\\controllers\\'.ucfirst($this->controller);

            if($this->_configuration->acl) {

                $this->secureStart();

            } else {

                $this->start();

            }

        } catch(\Exception $e) {


            trigger_error($e->getMessage(), E_USER_ERROR);


        }

    }

    /**
     * Autoloads configured resources
     *
     * @access private
     */

    private function autoload() {

        // autoload helpers and such.

        if ($autoload = Registry::get('_autoload')) {
            foreach ($autoload as $type => $component) {

                foreach($component as $comp) {
                    switch ($type) {
                        case 'helpers':

                            $this->load->helper($comp);

                            break;
                        case 'libraries':

                            foreach($comp as $lib => $args){
                                $this->load->library($lib, $args);
                            }

                            break;
                    }
                }
            }
        }
    }

    /**
     * Prepares the application registry, provides injection dependency.
     *
     * @access private
     */

    private function prepareRegistry() {

        Registry::add('_request', new Request());
        Registry::add('_router', new Router());
        Registry::add('_output', new Output());
        Registry::add('_validate', new Validate());
        Registry::add('_autoload', Registry::get('_load')->config('core','autoload'));

        $this->module = Registry::get('_module');
        $this->controller = Registry::get('_controller');
        $this->method = Registry::get('_method');
        $this->params = Registry::get('_params');

        Registry::add('_module', $this->module);
        Registry::add('_controller', $this->controller);
        Registry::add('_method', $this->method);
        Registry::add('_params', $this->params);

    }

    /**
     * Prepares the application core classes and dependencies
     *
     * @access private
     */

    private function prepareApplication() {

        $this->hook->dispatch('preRegistry');
        $this->prepareRegistry();
        $this->hook->dispatch('postRegistry');

        Registry::add('lang', $this->load->lang($this->load->config('core','language')));

        if($this->_configuration->database) {
            $this->hook->dispatch('preDatabase');
            $this->register('database');
            $this->hook->dispatch('postDatabase');
        }
        if($this->_configuration->cache) {
            $this->hook->dispatch('preCache');
            $this->register('cache');
            $this->hook->dispatch('postCache');
        }
        if($this->_configuration->session) {
            $this->hook->dispatch('preSession');
            $this->register('session');
            $this->hook->dispatch('postSession');
        }
        if($this->_configuration->acl) {
            $this->hook->dispatch('preACL');
            $this->register('acl');
            $this->hook->dispatch('postACL');
        }

    }

    /**
     * Registers core classes
     *
     * @access private
     * @param string $resource
     */

    private function register($resource) {

        switch($resource) {
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

    private function registerDatabase() {

        Registry::add('_database', new Database(
            'default', // you can name your db, for switching between..
            $this->_configuration->database['default']
        ));

    }

    /**
     * Registers the cache object
     *
     * @access private
     */

    private function registerCache() {

        $memcached = new \Memcached();
        $memcached->addServer($this->_configuration->cache->host,$this->_configuration->cache->port);

        Registry::add('_memcached', $memcached);


    }

    /**
     * Registers the session object
     *
     * @access private
     */

    private function registerSession() {

        if(!$this->_configuration->session['database']) {
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

    private function registerACL() {

        Registry::add('_acl', $this->_configuration->acl);

    }

    /**
     * Verifies the provided application request is a valid request
     *
     * @access private
     */

    private function verifyApplicationRequest() {

        $moduleExist = file_exists(APP_PATH.'/modules/'.$this->module);
        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, Registry::get('_method'));

        if(!$moduleExist) {
            $this->output = Router::show404($this->_configuration->modules['defaultModule'].'/404');
            return false;
        } else if(!$classExist) {
            $this->output = Router::show404($this->module.'/404');
            return false;
        } else if(!$methodExist) {
            $this->output = Router::show404($this->module.'/404');
            return false;
        }

        return true;

    }

    /**
     * Processes the application request
     *
     * @access private
     */

    private function start() {

        if(!$this->verifyApplicationRequest())
            return false;

        $this->hook->dispatch('preController');
        $this->instantiatedClass = new $this->class();
        $this->hook->dispatch('postController');

        $this->output = call_user_func_array(
            array(&$this->instantiatedClass, $this->method),
            $this->params
        );


    }

    /* ACL & USER STUFF
    * @TODO: I think perhaps, this can be handled somewhere else?..
    */

    /**
     * Handles client request within  ACL
     *
     * @access private
     */


    public function secureStart() {

        $request = Registry::get('_request');

        $userId = $request['uid'];
        $roleId = $request['roleId'];

        $ACL = new Acl($userId, $roleId);

        $authorizationCode = $ACL->hasAccessRights($this->module, $this->controller, $this->method);

        //@TODO:store access rights in registry for dynamic menu display
        switch($authorizationCode) {

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

    private function secureRedirect() {

        Registry::get('_request')->setFlashdata('alert', (object) array('info' => 'Please login to continue!'));

        $currentURL = currentURL();
        $currentURL = str_replace(baseURL(),'',$currentURL);
        $currentURL = base64_encode($currentURL);

        $redirect = $this->load->config('core','modules')[$this->module]['aclRedirect'];

        Router::redirect(baseURL($redirect.'?r='.$currentURL));

    }

    /**
     * Set 401 header, provide no access view if authenticated
     * and access is insufficient / protected
     *
     * @access private
     */

    private function noAccessRedirect() {

        return Router::showNoAccess($this->module.'/noaccess');

    }

    /**
     * Prepare application return value into a string
     *
     * @access public
     * @return string
     */

    public function __toString() {

        if(!$this->output)
            $this->output = '';

        $this->hook->dispatch('postApplication');

        return $this->output;
    }

}
