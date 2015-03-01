<?php

namespace core;
//@TODO: should router class be completely static.. ?
Class App
{

    private $output = null;
    private $class;
    private $instantiatedClass;
    private $module;
    private $controller;
    private $method;

    public function __construct() {
        //@TODO: go unset unnececessary vars
        try {

            Registry::add('_loader', new Load());
            $this->loader = Registry::get('_loader'); //@TODO these need to be moved to an array called "system" in the registry, and write protected, _ is lame.
            $this->configuration = (object) array(
                'database' => $this->loader->config('core', 'database'),
                'session' => $this->loader->config('core', 'session'),
                'cache' => $this->loader->config('core','cache'),
                'acl' => $this->loader->config('core','acl'),
                'modules' => $this->loader->config('core','modules'),
                'hooks' => $this->loader->config('core','hooks')
            );

            Registry::add('_configuration',$this->configuration);

            $this->prepareApplication();
            $this->autoload();

            $this->class = 'app\\modules\\'.Registry::get('_module').'\\controllers\\'.ucfirst($this->controller);

            if($this->configuration->acl) {

                $this->secureStart();

            } else {

                $this->start();

            }

        } catch(\Exception $e) {

            die($e->getMessage());

        }

    }


    private function autoload() {

        // autoload helpers and such.

        if ($autoload = Registry::get('_autoload')) {
            foreach ($autoload as $type => $component) {

                foreach($component as $comp) {
                    switch ($type) {
                        case 'helpers':

                            Registry::get('_loader')->helper($comp);

                            break;
                        case 'libraries':

                            foreach($comp as $lib => $args){
                                Registry::get('_loader')->library($lib, $args);
                            }

                            break;
                    }
                }
            }
        }
    }

    private function prepareApplication() {

        $this->prepareRegistry();

        Registry::add('lang', $this->loader->lang($this->loader->config('core','language')));

        if($this->configuration->database) {
            $this->prepare('database');
        }

        if($this->configuration->cache) {
            $this->prepare('cache');
        }

        if($this->configuration->session) {
            $this->prepare('session');
        }

        if($this->configuration->acl) {
            $this->prepare('acl');
        }

    }

    private function prepareRegistry() {

        Registry::add('_request', new Request());
        Registry::add('_router', new Router());
        Registry::add('_output', new Output());
        Registry::add('_validate', new Validate());
        Registry::add('_autoload', Registry::get('_loader')->config('core','autoload'));

        $this->module = Registry::get('_module');
        $this->controller = Registry::get('_controller');
        $this->method = Registry::get('_method');
        $this->params = Registry::get('_params');

        Registry::add('_module', $this->module);
        Registry::add('_controller', $this->controller);
        Registry::add('_method', $this->method);
        Registry::add('_params', $this->params);


    }

    private function prepare($resource, $callback = false) {

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

        if($callback && is_callable($callback)) $callback();

    }

    private function registerDatabase() {

        Registry::add('_database', new Database(
            'default', // you can name your db, for switching between..
            $this->configuration->database['default']
        ));

    }

    private function registerCache() {

        $memcached = new \Memcached();
        $memcached->addServer($this->configuration->cache->host,$this->configuration->cache->port);

        Registry::add('_memcached', $memcached);


    }

    private function registerSession() {

        if(!$this->configuration->session['database']) {
            throw new \Exception('Not supported yet..');
        } else {

            new SessionHandler(Registry::get('_database'), 'securitycode', 7200, true, false, 1, 100);
        }

    }

    private function registerACL() {

        Registry::add('_acl', $this->configuration->acl);

    }

    private function verifyAppProcess() {

        $moduleExist = file_exists(APP_PATH.'/modules/'.$this->module);
        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, Registry::get('_method'));

        if(!$moduleExist) {
            $this->output = Router::show404($this->configuration->modules['defaultModule'].'/404');
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

    private function start() {

        if(!$this->verifyAppProcess())
            return false;

        $this->instantiatedClass = new $this->class();

        $this->output = call_user_func_array(
            array(&$this->instantiatedClass, $this->method),
            $this->params
        );


    }

    /* ACL & USER STUFF */

    public function secureStart() {

        //enable hooks ?

        $rbal = new Acl(Registry::get('_request')->session('uid'), Registry::get('_request')->session('role_id'));


        $authorizationCode = $rbal->hasAccessRights($this->module, $this->controller, $this->method);

        //@TODO:store access rights in registry for dynamic menu display
        switch($authorizationCode) {

            case '1':
                $this->start();
                break;
            case '2':
                $this->loginRedirect();
                break;

            case '3': //@TODO: setup module 404's.
                $this->output = $this->noAccessRedirect();
                break;
        }
    }

    private function loginRedirect() {

        Registry::get('_request')->setFlashdata('alert', (object) array('info' => 'Please login to continue!'));

        $currentURL = currentURL();
        $currentURL = str_replace(baseURL(),'',$currentURL);
        $currentURL = base64_encode($currentURL);

        $loginRedirect = $this->loader->config('core','modules')[$this->module]['loginRedirect'];

        Router::redirect(baseURL($loginRedirect.'?r='.$currentURL));

    }

    private function noAccessRedirect() {

        return Router::showNoAccess($this->module.'/noaccess');

    }

    /* Output rendering */

    public function __toString() {
        if(!$this->output) return '';
        return $this->output;
    }

}
