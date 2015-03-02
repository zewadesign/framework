<?php

namespace core;
use \Exception as Exception;

/**
 * This class handles loading of resources
 *
 * <code>
 *
 * $this->load->model('model');
 * $controller = $this->load->controller('module','controller',['optional','parameters']);
 * $view = $this->load->view('relative/to/module',['some'=>'data'],'optional/layout');
 * $library = $this->load->library('relative/to/library',['constructor','arguments']);
 * $this->load->helper('relative/to/helpers',required = (true|false));
 * $config = $this->load->config('file', 'reference');
 * $lang = $this->load->lang('file', 'reference');
 *
 * </code>
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */


Class Load
{

    /**
     * Container for configured language
     *
     * @access private
     * @var mixed false if not loaded, array if loaded
     */

    private $lang = false;

    /**
     * Container for configured settings
     *
     * @access private
     * @var array
     */

    private $config = [];

    /**
     * Container for loaded helper references
     *
     * @access private
     * @var array
     */

    private $helper = [];

    /**
     * Loads a model
     *
     * @access public
     * @param string $model to load
     * @return object
     * @throws Exception when a model does not exist
     */


    public function model($model) {

        $class = 'app\\models\\'.ucfirst($model);

        if (!class_exists($class)) {

            throw new \Exception($model.' does not exist.');

        }

        return new $class;

    }

    /**
     * Loads a controller
     *
     * @access public
     * @param string $module module where controller is located
     * @param string $controller controller to load
     * @param array $args arguments to provide to controller
     * @return object invoked based on arguments, or instance
     * @throws Exception when a controller does not exist
     */

    public function controller($module, $controller, $args = []) {

        $class = 'app\\modules\\'.$module.'\\controllers\\'.ucfirst($controller);

        if (!class_exists($class)) {

            throw new Exception($module.'::'.$controller.' does not exist.');

        }

        if(!empty($args)) {
            return new $class($args);
        }

        return $class::getInstance();

    }


    /**
     * Loads a view
     *
     * @access public
     * @param string $view relative path for the view
     * @param array $data array of data to expose to view
     * @param string $layout relative path for the layout
     * @return string processed view/layout
     * @throws Exception when requires values are missing
     * @throws Exception when a view can not be found
     * @throws Exception when a layout can not be found
     */

    public function view($view = '', $data = [], $layout = 'layout') {

        $module = Registry::get('_module');

        if($view != '') {
            $file = APP_PATH . DS . 'modules' . DS . $module . DS . 'views' . DS . strtolower($view) . '.php';
            if (file_exists($file)) {

                $data['view'] = $this->render($file, $data);

            } else {

                throw new Exception('View: "' . $view . '" could not be found.');

            }
        }

        if($layout != false) {
            $file = APP_PATH . DS . 'layouts' . DS . $module.DS.strtolower($layout) . '.php';

            if (file_exists($file)) {
                return $this->render($file, $data);
            }

            throw new Exception('Layout: "' . $layout . '" could not be found.');
        }

        throw new Exception('Invalid parameters for view loading.');

    }


    /**
     * Loads a library
     *
     * @access public
     * @param string $file file name of library class
     * @param array $args array of arguments for constructor
     * @return object instantiated library
     * @throws Exception when a library can not be found
     */

    public function library($file, $args=[]) {
        if (class_exists($file)) {
            $obj = new $file($args);
            return $obj;
        }

        throw new \Exception('Library: "'.$file.'" could not be found.');
    }

    /**
     * Loads a helper
     *
     * @access public
     * @param string $file file name of helper
     * @param boolean $require true to require, false to include
     * @return resource
     * @throws Exception when a helper file can not be found
     */

    public function helper($file, $require = false) {

        $path = APP_PATH.DS.'helpers'.DS.strtolower($file).'.php';

        if (isset($this->helpers[$path])) {
            return $this->helpers[$path];
        }

        if (!file_exists($path)) {
            throw new \Exception('Helper: "'.$file.'" could not be found.');
        }
        $this->helpers[$path] = ($require ? require($path) : include($path));

        return $this->helpers[$path];

    }

    /**
     * Loads a configuration item
     *
     * @access public
     * @param string $file file name of configuration
     * @param string $item reference to index in configuration
     * @return array|string
     * @throws Exception when a config file can not be found
     * @throws Exception when a config item can not be found
     */


    public function config($file='', $item='') {

        if (isset($this->config[$file])) {
            return (isset($this->config[$file][$item]) ? $this->config[$file][$item] : $this->config[$file]);
        }
    
        if ($file != '' AND file_exists(APP_PATH.DS.'config'.DS.$file.'.php')) {

            if (!file_exists(APP_PATH.DS.'config'.DS.$file.'.php')) {
                throw new \Exception($file.' could not be found');
            }

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

    /**
     * Loads a language item
     *
     * @access public
     * @param string $file file name of configuration
     * @param string $item reference to index in configuration
     * @return array|string
     * @throws Exception when a language path isn't provided.
     * @throws Exception when a language item can not be found
     */


    public function lang($file = '', $item = '') {

        if ($this->lang !== false) {
            return $this->lang;
        }

        if($file == '')
            throw new Exception('The language path can not be empty.');

        if (file_exists(APP_PATH.DS.'lang'.DS.$file.'.php')) {

            include(APP_PATH.DS.'lang'.DS.$file.'.php');

            if(is_array($$file)) {

                $this->lang = $$file;

                return $this->lang;
            }

        } else {
            throw new Exception('Language file: '.$file.' could not be found');
        }

        return false;

    }


    /**
     * Processes view/layouts and exposes variables to the view/layout
     *
     * @access private
     * @param string $file file being rendered
     * @param array $data data to load into view/layout
     * @return string processed content
     */


    //@TODO: come back and clean up this and the way the view receives stuff
    private function render($file, $data=[]) {
        // make sure..
        // INCLUDE SOME BASE STUFF HERE, BASE URL FOR ONE.
        if(!file_exists($file)) {
            return null;
        }

        ob_start();

        if (is_array($data)) {
            extract($data); // yuck. could produce undeclared errors. hmm..
        }

        $app = (object) [
            'request' => Registry::get('_request'),
            'loader' => Registry::get('_loader'),
            'output' => Registry::get('_output'),
            'lang' => Registry::get('_lang')
        ];

        //should i set $this->data in abstract controller, and provide all access vars ? seems bad practice..

        include($file);

        $return = ob_get_contents();

        ob_end_clean();
        
        return $return;
    }
}