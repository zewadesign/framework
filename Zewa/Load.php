<?php

namespace Zewa;

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
     * Container for configured settings
     *
     * @access private
     * @var array
     */
    private $config = [];

    /**
     * Create instance
     */

    public function __construct()
    {
        self::$instance = $this;
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
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function getInstance()
    {

        try {

            if (self::$instance === null) {
                throw new \Exception('Unable to get an instance of the load class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}
