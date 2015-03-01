<?php

namespace core;

Class Load
{

    public static $instance;
    private $lang = false;
    private $config = array();
    private $helper = array();

    public function __construct() {
//
//        self::$instance = $this;

    }

    public function model($model) {

        $class = 'app\\models\\'.ucfirst($model);

        if (!class_exists($class)) {

            throw new \Exception($model.' does not exist.');

        }

        return new $class;

    }

    public function controller($module, $controller, $args=array()) {

        $class = 'app\\modules\\'.$module.'\\controllers\\'.ucfirst($controller);

        if (!class_exists($class)) {

            throw new \Exception($module.'::'.$controller.' does not exist.');

        }

        if(!empty($args)) {
            return new $class($args);
        }

        return $class::getInstance();

    }

    public function view($layout = 'default', $view = false, $data = array()) {

        if($view) {
            $file = APP_PATH . DS . 'modules' . DS . Registry::get('_module') . DS . 'views' . DS . strtolower($view) . '.php';
            if (file_exists($file)) {

                $data['view'] = $this->render($file, $data);

            } else {

                throw new \Exception('View: "' . $view . '" could not be found.');

            }
        }

        if($layout != false) {
            $file = APP_PATH . DS . 'layouts' . DS . strtolower($layout) . '.php';

            if (file_exists($file)) {
                return $this->render($file, $data, true);
            }

            throw new \Exception('Layout: "' . $layout . '" could not be found.');
        }

        throw new \Exception('Invalid parameters for view loading.');

    }

    public function library($library, $args=array()) {
        if (class_exists($library)) {
            $obj = new $library($args);
            return $obj;
        }

        throw new \Exception('Library: "'.$library.'" could not be found.');
    }

    public function helper($file, $require = false) {

        $file = APP_PATH.DS.'helpers'.DS.strtolower($file).'.php';

        if (isset($this->helpers[$file])) {
            return $this->helpers[$file];
        }

        $this->helpers[$file] = ($require ? require($file) : include($file));

        return $this->helpers[$file];

    }
    
    public function config($file=null, $item=null) {

        if (isset($this->config[$file])) {
            return (isset($this->config[$file][$item]) ? $this->config[$file][$item] : $this->config[$file]);
        }
    
        if (!is_null($file) AND file_exists(APP_PATH.DS.'config'.DS.$file.'.php')) {

            include(APP_PATH.DS.'config'.DS.$file.'.php');

            if (is_array($$file)) {

                $this->config[$file] = $$file;

                if (!is_null($item) AND !isset($this->config[$file][$item])) {

                    throw new \Exception($item.' could not be found in '.$file);

                }

                return (isset($this->config[$file][$item]) ? $this->config[$file][$item] : $this->config[$file]);
            }

        } elseif (is_null($file)) {
            return $this->config;
        }
        
        return array();
    }

    public function lang($file=null, $item=null) {

        if ($this->lang !== false) {
            return $this->lang;
        }

        if (!is_null($file) AND file_exists(APP_PATH.DS.'lang'.DS.$file.'.php')) {

            include(APP_PATH.DS.'lang'.DS.$file.'.php');

            if(is_array($$file)) {

                $this->lang = $$file;

                return $this->lang;
            }

        }

        return false;

    }

    public function render($file, $data=array(), $isLayout = false) {
        // make sure..
        // INCLUDE SOME BASE STUFF HERE, BASE URL FOR ONE.
        if(!file_exists($file)) {
            return null;
        }

        ob_start();

        if (is_array($data)) {
            extract($data); // yuck. could produce undeclared errors. hmm..
        }

        $app = (object) array('request' => Registry::get('_request'), 'loader' => Registry::get('_loader'));

        //should i set $this->data in abstract controller, and provide all access vars ? seems bad practice..
        $_render = Registry::get('_output');

        if($isLayout) {
            $_lang = json_encode(Registry::get('lang'));
        }
        include($file);

        $return = ob_get_contents();

        ob_end_clean();
        
        return $return;
    }
}