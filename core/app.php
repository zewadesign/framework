<?php

namespace core;
    //@TODO: handle var setting based on loaded configs.. e.g. if no session, no referral redirect info
    //or, no database, no acl.
//@TODO: should router class be completely static.. ?
Class App
{

    private $output = null;
    private $class;
    private $instantiatedClass;
    private $activeModule;
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
                'acl' => $this->loader->config('core','acl')
            );

            $this->prepareApplication();

            //@TODO: select addons here too


            $this->class = 'app\\modules\\'.Registry::get('_module').'\\controllers\\'.ucfirst(Registry::get('_controller'));

            $this->autoload();


            //@TODO granular access parameter scopes for module, controller and method, so I can check which individual
            //props are accessible, to handle 404ing, etc

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

        $this->module = (Registry::get('_module') ? Registry::get('_module') : false);
        $this->controller = (Registry::get('_controller') ? Registry::get('_controller') : false);
        $this->method = (Registry::get('_method') ? Registry::get('_method') : false);
        $this->params = array_slice(Registry::get('_router')->url['segments'], 3);


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

        $this->prepareRegistry();
    }

    private function prepareRegistry() {

        Registry::add('_request', new Request());
        Registry::add('_router', new Router());
        Registry::add('_output', new Output());
        Registry::add('_validate', new Validate());
        Registry::add('_autoload', Registry::get('_loader')->config('core','autoload'));
        Registry::add('_module', $this->module);
        Registry::add('_controller', $this->controller);
        Registry::add('_method', $this->method);
        Registry::add('_params', $this->params);

        /*
         * @TODO: move anything that sets private vars via registry to the class setting (e.g. the below
         * methods would be called from the __construct of router.
         */
        Registry::add('rootPath', ROOT_PATH);
        Registry::add('baseURL', Registry::get('_router')->baseURL());
        Registry::add('uri', Registry::get('_router')->uri());
        Registry::add('currentURL', Registry::get('_router')->url());

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


    private function start($secured = false) {


        $classExist = class_exists($this->class);
        $methodExist = method_exists($this->class, Registry::get('_method'));


        if (!$classExist) { //@TODO if module is present, but class doesn't exist....

            if (!$this->controller) {

                $baseRedirect = $this->loader->config('core','modules')[$this->module]['baseRedirect'];
                Router::redirect(Registry::baseURL($baseRedirect));

            } else {

                $this->output = Router::show404($this->module.'/404');

            }

        } elseif ($classExist && !$methodExist) {

            $this->output = Router::show404($this->module.'/404');


        } else {


            $this->instantiatedClass = new $this->class();

            $this->output = call_user_func_array(
                array(&$this->instantiatedClass, $this->method),
                $this->params
            );

        }


    }

    /* ACL & USER STUFF */

    public function secureStart() {

        //enable hooks ?

        $rbal = new Acl(Registry::get('_request')->session('uid'), Registry::get('_request')->session('role_id'));


        $authorizationCode = $rbal->hasAccessRights($this->module, $this->controller, $this->method);

        //@TODO:store access rights in registry for dynamic menu display
        switch($authorizationCode) {

            case '1':
                $this->start(true);
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
        return $this->output;
    }

}
