<?php
namespace Zewa;

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

    private $getContainer = [];

    /**
     * normalized $_POST superglobal
     *
     * @var array
     * @access private
     */

    private $postContainer = [];

    /**
     * normalized $_DELETE superglobal
     *
     * @var array
     * @access private
     */

    private $deleteContainer = [];

    /**
     * normalized $_PUT superglobal
     *
     * @var array
     * @access private
     */

    private $putContainer = [];

    /**
     * normalized $_SESSION superglobal
     *
     * @var array
     * @access private
     */

    private $sessionContainer = [];

    /**
     * normalized $_COOKIE superglobal
     *
     * @var array
     * @access private
     */

    private $cookieContainer = [];

    /**
     * normalized $_FILES superglobal
     *
     * @var array
     * @access private
     */

    private $filesContainer = [];

    /**
     * normalized $_SERVER superglobal
     *
     * @var array
     * @access private
     */

    private $serverContainer = [];


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
        if($this->configuration->session !== false && $this->configuration->session->flashdataId) {
            $this->flashdataId = $this->configuration->session->flashdataId;
        }

//        $config = \HTMLPurifier_Config::createDefault();
//        $this->purifier = new \HTMLPurifier($config);

        if(!empty($_SESSION)) {
            $this->sessionContainer = $this->_normalize($_SESSION);
        }
        $this->registerFlashdata();

        $this->getContainer = $this->_normalize($_GET);
        $this->postContainer = $this->_normalize($_POST);
        $this->cookieContainer = $this->_normalize($_COOKIE);
        $this->filesContainer = $this->_normalize($_FILES);
        $this->serverContainer = $this->_normalize($_SERVER);
        if($this->server('REQUEST_METHOD') === 'PUT') {
            parse_str(file_get_contents('php://input', "r"), $this->put);
            $this->putContainer = $this->_normalize($this->put);
        } else if($this->server('REQUEST_METHOD') === 'DELETE') {
            parse_str(file_get_contents('php://input', "r"), $this->delete);
            $this->deleteContainer = $this->_normalize($this->delete);
        }
    }


    /**
     * Processes current requests flashdata, recycles old.
     * @access private
     */
    private function registerFlashdata()
    {

        if (!empty($this->sessionContainer[$this->flashdataId])) {

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
        $this->sessionContainer[$name] = $value;

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
     * Remove session data
     * @access public
     *
     * @params string $index
     */
    public function removeSession($index)
    {

        unset($this->sessionContainer[$index]);
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
        try {

            if ((!is_array($index) && $value !== false)
                || (!is_object($index) && $value !== false)
            ) {
                $index = array($index => $value);

            } elseif (is_object($index)) {

                $index = (array) $index;

            } else {
                if (!is_array($index)) {
                    throw new Exception\TypeException("Invalid where parameters");
                }

            }

            foreach ($index as $k => $v) {
                $_SESSION[$k] = $v;
                $this->sessionContainer = $this->_normalize($_SESSION);
            }

        } catch (Exception\TypeException $e) {

            echo "<strong>TypeException:</strong> <br/>";
            echo $e->getMessage();
            exit;

        }
    }

    /**
     * Dumps all session data
     * @access public
     */
    public function destroySession()
    {

        $_SESSION = [];

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
        if (is_array($data) || is_object($data)) {
            $data = (array) $data;
            foreach ($data as $key => $value) {
                unset($data[$key]);

                $data[$this->_normalize($key)] = $this->_normalize($value);
            }
        } else {
            $data = trim($data);
//            if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
//                $current_encoding = mb_detect_encoding($data);
//
//                if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
//                    $data = iconv($current_encoding, 'UTF-8', $data);
//                }
//            }
            //Global XXS?
            // This is not sanitary.  FILTER_SANITIZE_STRING doesn't do much.

//            $data = filter_var($data, FILTER_SANITIZE_STRING);

            if (is_numeric($data)) {
                if(is_int($data) || ctype_digit(trim($data, '-'))) {
                    $data = (int) $data;
                } else if($data == (string)(float)$data) {
                    $data = (float) $data;
                }
            } else {
//                $data = $this->purifier->purify($data);
            }
        }

        return $data;
    }

    public function __call($name, $arguments)
    {
        $accepted = ['post', 'put', 'delete', 'get', 'server', 'session'];

        try {
            if(in_array($name, $accepted)) {

                $container = $name . 'Container';
                $container = $this->$container;

                $argument = ! empty( $arguments[0] ) ? $arguments[0] : false;

                if($argument === false && !empty($container)) {
                    return $container;
                }
                if( ! empty ( $container[$argument] ) ) {
                    if(!is_array($container[$argument]) && strlen($container[$argument]) > 0 || is_array($container[$argument])) {
                        return $container[$argument];
                    }
                }

                return ! empty ( $arguments[1] ) ? $arguments[1] : false;
            }

            throw new Exception\FunctionException('Method ' . $name . ' does not exist.');
        } catch(Exception\FunctionException $e) {

            echo "<strong>FunctionException:</strong> <br/>";
            echo $e->getMessage();
            exit;

        }
    }
}