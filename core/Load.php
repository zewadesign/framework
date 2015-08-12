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
class Load
{
    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $instance;

    /**
     * System configuration
     *
     * @var object
     */
    protected $configuration;

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
     * Create instance
     */

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Loads a model
     *
     * @access public
     *
     * @param string $model to load
     *
     * @return object
     * @throws Exception when a model does not exist
     */
    public function model($model)
    {

        $class = 'app\\models\\' . ucfirst($model);

        if (!class_exists($class)) {
            // Sometimes you \Exception
            throw new \Exception($model . ' does not exist.');

        }
        // Your controllers below (albeit a strange approach) can be singletons but models are not?
        return new $class;

    }

    /**
     * Loads a controller
     *
     * @access public
     *
     * @param string $module module where controller is located
     * @param string $controller controller to load
     * @param array $args arguments to provide to controller
     *
     * @return object invoked based on arguments, or instance
     * @throws Exception when a controller does not exist
     */
    public function controller($module, $controller, $args = [])
    {

        $class = 'app\\modules\\' . $module . '\\controllers\\' . ucfirst($controller);

        if (!class_exists($class)) {
            // Other times you don't \Exception.  Doesn't matter I suppose.
            throw new Exception($module . '::' . $controller . ' does not exist.');

        }

        if (!empty($args)) {
            // if we pass arguments do we get a new instance of $controller class?
            return new $class($args);
        }
        // if we don't pass arguments we get a singleton. Interesting approach.
        return $class::getInstance();

    }

    /**
     * Loads a helper
     *
     * @access public
     *
     * @param string $file file name of helper
     * @param boolean $require true to require, false to include
     *
     * @return resource
     * @throws Exception when a helper file can not be found
     */
    public function helper($file, $require = false)
    {
        // Shouldn't helpers just be auto-loaded static class methods?
        $path = APP_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . strtolower($file) . '.php';

        if (isset($this->helper[$path])) {
            return $this->helper[$path];
        }

        if (!file_exists($path)) {
            throw new \Exception('Helper: "' . $file . '" could not be found.');
        }
        $this->helper[$path] = ($require ? require($path) : include($path));

        return $this->helper[$path];

    }

    /**
     * Loads a configuration item
     *
     * @access public
     *
     * @param string $file file name of configuration
     * @param string $item reference to index in configuration
     *
     * @return array|string
     * @throws Exception when a config file can not be found
     * @throws Exception when a config item can not be found
     */
    public function config($file = '', $item = '')
    {

        try {

            if (isset($this->config[$file])) {
                // you know we throw an exception later if the $item doesn't exist.
                $config = (isset($this->config[$file][$item]) ? $this->config[$file][$item] : $this->config[$file]);
                return json_decode(json_encode($config));
            }

            if (is_null($file)) {
                // This will never happen unless you intentionally Load->config(null);
                return json_decode(json_encode($this->config));
            }

            if ($file != '' and file_exists(APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $file . '.php')) {
                if (!file_exists(APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $file . '.php')) {
                    throw new \Exception($file . ' could not be found');
                }
                include(APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $file . '.php');

                if (is_array($$file)) {

                    $this->config[$file] = $$file;

                    if (!is_null($item) and !isset($this->config[$file][$item])) {
                        // Why do we throw an exception down here if the $item can't be found
                        // but up above if the config[$file] exists but the $item doesn't we just return the config[$file]
                        throw new \Exception($item . ' could not be found in ' . $file);

                    }

                    $config = (isset($this->config[$file][$item]) ? $this->config[$file][$item] : $this->config[$file]);
                    return json_decode(json_encode($config));

                }

            }
        } catch(\Exception $e) {
            echo "Caught exception: " . $e->getMessage() . "\n";
        }

        // Why does this return an empty array when we request Loader->config() but
        // if we Loader->config(null) we pass back $this->config as seen above?
        return (object)[];
    }

    /**
     * Loads a language item
     *
     * @access public
     *
     * @param string $file file name of configuration
     * @param string $item reference to index in configuration
     *
     * @return array|string
     * @throws Exception when a language path isn't provided.
     * @throws Exception when a language item can not be found
     */
    public function lang($file = '', $item = '')
    {

        if ($this->lang !== false) {
            return $this->lang;
        }

        if ($file == '') {
            throw new Exception('The language path can not be empty.');
        }
        // Not using brackets makes this stuff harder to read.
        // Pick any code style standard and try it out

        if (file_exists(APP_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file . '.php')) {
            include(APP_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file . '.php');

            if (is_array($$file)) {
                $this->lang = $$file;

                return $this->lang;
            } // So if I have a lang file that's got the right filename but not the right content, $$file
            // we get "Notice: Undefined variable:" instead of a fancy exception.

        } else {
            throw new Exception('Language file: ' . $file . ' could not be found');
        }

        return false;

    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function &getInstance()
    {

        try {

            if (self::$instance === null) {
                throw new Exception('Unable to get an instance of the load class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}
