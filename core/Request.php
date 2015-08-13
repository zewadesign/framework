<?php
namespace core;

use \Exception as Exception;

/**
 * Handles everything relating to request variables/globals/properties
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Request
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
    private $configuration;


    /**
     * normalized $_GET superglobal
     *
     * @var array
     * @access private
     */

    private $get = [];

    /**
     * normalized $_POST superglobal
     *
     * @var array
     * @access private
     */

    private $post = [];

    /**
     * normalized $_SESSION superglobal
     *
     * @var array
     * @access private
     */

    private $session = [];

    /**
     * normalized $_COOKIE superglobal
     *
     * @var array
     * @access private
     */

    private $cookie = [];

    /**
     * normalized $_FILES superglobal
     *
     * @var array
     * @access private
     */

    private $files = [];

    /**
     * normalized $_SERVER superglobal
     *
     * @var array
     * @access private
     */

    private $server = [];


    /**
     * Flashdata container
     *
     * @var array
     * @access private
     */

    private $flashdata = [];

    /**
     * Flashdata identifier
     *
     * @var string
     * @access private
     * @TODO: move flashdata to sessionhandler, make available here with other request vars still
     */


    private $flashdataId = '_z_session_flashdata';


    /**
     * Normalizes superglobals, handles flashdata
     */

    public function __construct()
    {
        self::$instance = $this;
        $this->configuration = App::getConfiguration();

        if($this->configuration->session->flashdataId) {
            $this->flashdataId = $this->configuration->session->flashdataId;
        }

        $this->registerFlashdata();
        if(!empty($_SESSION)) {
            $this->session = $this->_normalize($_SESSION);
        }
        $this->get = $this->_normalize($_GET);
        $this->post = $this->_normalize($_POST);
        $this->cookie = $this->_normalize($_COOKIE);
        $this->files = $this->_normalize($_FILES);
        $this->server = $this->_normalize($_SERVER);

    }


    /**
     * Processes current requests flashdata, recycles old.
     * @access private
     */
    private function registerFlashdata()
    {


        if ($this->session($this->flashdataId)) {

            $this->flashdata = unserialize($this->session($this->flashdataId));
            // and destroy the temporary session variable
            unset($_SESSION[$this->flashdataId]);


            if (!empty($this->flashdata)) {
                // iterate through all the entries
                foreach ($this->flashdata as $variable => $data) {
                    // increment counter representing server requests
                    $this->flashdata[$variable]['inc'] ++;

                    // if we're past the first server request
                    if ($this->flashdata[$variable]['inc'] > 1) {
                        // unset the session variable
                        unset($_SESSION[$variable]);

                        // stop tracking
                        unset($this->flashdata[$variable]);

                    }

                }

                // if there is any flashdata left to be handled
                if (!empty($this->flashdata)) {
// store data in a temporary session variable
                    $_SESSION[$this->flashdataId] = serialize($this->flashdata);
                }
            }


        }

    }

    /**
     * Sets flashdata
     * @access public
     *
     * @params string $name
     * @params mixed $value
     */

    public function setFlashdata($name, $value)
    {

        // set session variable
        $this->session[$name] = $value;

        // initialize the counter for this flashdata
        $this->flashdata[$name] = array(
            'value' => $value,
            'inc'   => 0
        );

        $_SESSION[$this->flashdataId] = serialize($this->flashdata);

    }

    /**
     * Gets flashdata
     * @access public
     *
     * @params string $name
     */

    public function getFlashdata($name = false, $default = false)
    {
        if ($name === false && !empty($this->flashdata)) {
            return $this->flashdata;
        }
        if($name !== false) {
            if(!empty($this->flashdata[$name])) {
                return $this->flashdata[$name];
            }
        }

        return $default;
    }

    /**
     * Get normalized $_POST data
     * @access public
     *
     * @params string $index
     *
     * @return mixed
     */

    public function post($index = false, $default = false)
    {

        if ($index === false && !empty($this->post)) {
            return $this->post;
        }

        if (isset($this->post[$index])) {
            return $this->post[$index];
        }

        return $default;

    }


    /**
     * Get normalized $_GET data
     * @access public
     *
     * @params string $index
     *
     * @return mixed
     */
    public function get($index = false, $default = false)
    {

        if ($index === false && !empty($this->get)) {
            return $this->get;
        }

        if (isset($this->get[$index])) {
            return $this->get[$index];
        }

        return $default;

    }


    /**
     * Get normalized $_SESSION data
     * @access public
     *
     * @params string $index
     *
     * @return mixed
     */
    public function session($index = false, $default = false)
    {

        if ($index === false && !empty($this->session)) {
            return $this->session;
        }

        if (isset($this->session[$index])) {
            return $this->session[$index];
        }

        return $default;

    }


    /**
     * Remove session data
     * @access public
     *
     * @params string $index
     */
    public function removeSession($index)
    {

        unset($this->session[$index]);
        unset($_SESSION[$index]);

    }

    /**
     * Set session data
     * @access public
     *
     * @params string $index
     * @params mixed $value
     */

    public function setSession($index = false, $value = false)
    {
        if ((!is_array($index) && $value !== false)
            || (!is_object($index) && $value !== false)
        ) {
            $index = array($index => $value);

        } elseif (is_object($index)) {
            $index = (array) $index;

        } else {
            if (!is_array($index)) {
                throw new \Exception("Invalid where parameters");
            }

        }

        foreach ($index as $k => $v) {
            $_SESSION[$k] = $v;
            $this->session = $this->_normalize($_SESSION);
        }

    }

    /**
     * Dumps all session data
     * @access public
     */
    public function destroySession()
    {

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

    }

    /**
     * Normalizes data
     * @access private
     * @TODO: expand functionality, set/perform based on configuration
     */
    private function _normalize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);

                $data[$this->_normalize($key)] = $this->_normalize($value);
            }
        } else {
            if (is_string($data)) {
//                if(strpos($data, "\r") !== FALSE) {
                $data = trim($data);
//                }

                if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
                    $current_encoding = mb_detect_encoding($data);

                    if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                        $data = iconv($current_encoding, 'UTF-8', $data);
                    }
                }
                //Global XXS?
                // This is not sanitary.  FILTER_SANITIZE_STRING doesn't do much.
                $data = filter_var($data, FILTER_SANITIZE_STRING);

            } elseif (is_numeric($data)) {
                $data = (int) $data;
            }

        }

        return $data;
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
                throw new \Exception('Unable to get an instance of the request class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}