<?php

namespace core;

class Request
{

    private $get = false;
    private $post = false;
    private $session = false;
    public $cookie = false;
    public $files = false;
    public $server = false;
    private $flashdata;
    private $flashdataIdentifier;

//    public function __get($key) {
//
//        if(isset($this->$key)) {
//            return $this->$key;
//        } else {
//            return false;
//        }
//
//    }
    //@TODO: move flashdata to sessionhandler, make available here with other request vars still
    public function __construct() {

        if(Registry::get('_configuration')->session) {

            // assume no flashdata
            $this->flashdata = array();

            $this->flashdataIdentifier = '_session_flashdata_12971';
            // if there are any flashdata variables that need to be handled

            if (isset($_SESSION[$this->flashdataIdentifier])) {
                // store them

                $this->flashdata = unserialize($_SESSION[$this->flashdataIdentifier]);
                // and destroy the temporary session variable
                unset($_SESSION[$this->flashdataIdentifier]);


                if (!empty($this->flashdata)) {

                    // iterate through all the entries
                    foreach ($this->flashdata as $variable => $data) {

                        // increment counter representing server requests
                        $this->flashdata[$variable]['inc']++;

                        // if we're past the first server request
                        if ($this->flashdata[$variable]['inc'] > 1) {

                            // unset the session variable
                            unset($_SESSION[$variable]); //@TODO: add flashkey identifier?

                            // stop tracking
                            unset($this->flashdata[$variable]);

                        }

                    }

                    // if there is any flashdata left to be handled
                    if (!empty($this->flashdata))

                        // store data in a temporary session variable
                        $_SESSION[$this->flashdataIdentifier] = serialize($this->flashdata);
                }


            }

            $this->session = $this->_normalize($_SESSION);

        }

	    $this->get = $this->_normalize($_GET);
        $this->post = $this->_normalize($_POST);
        $this->cookie = $this->_normalize($_COOKIE);
        $this->files = $this->_normalize($_FILES);
        $this->server = $this->_normalize($_SERVER);

        // handle flashdata after script execution
//        register_shutdown_function(array($this, 'manageFlashdata'));

    }

//    public function __destruct() {
//
//        $this->manageFlashdata();
//
//    }

    public function manageFlashdata() {

        // if there is flashdata to be handled
        if (!empty($this->flashdata)) {

            // iterate through all the entries
            foreach ($this->flashdata as $variable => $data) {

                // increment counter representing server requests
                $this->flashdata[$variable]['inc']++;

                // if we're past the first server request
                if ($this->flashdata[$variable]['inc'] > 1) {

                    // unset the session variable
                    unset($_SESSION[$variable]); //@TODO: add flashkey identifier?

                    // stop tracking
                    unset($this->flashdata[$variable]);

                }

            }

            // if there is any flashdata left to be handled
            if (!empty($this->flashdata))

                // store data in a temporary session variable
                $_SESSION[$this->flashdataIdentifier] = serialize($this->flashdata);

        }

    }

    public function setFlashdata($name, $value){

        // set session variable
        $this->session[$name] = $value;

        // initialize the counter for this flashdata
        $this->flashdata[$name] = array(
            'value' => $value,
            'inc' => 0
        );

        $_SESSION[$this->flashdataIdentifier] = serialize($this->flashdata);

    }

    public function getFlashdata($name = false) {

        if($name) {

            return (!empty($this->flashdata[$name]) ? $this->flashdata[$name]['value'] : false);

        } else {

            return $this->flashdata;

        }

    }

    public function post($index = false) {

        if($index === false && isset($this->post)) return $this->post;

        if(isset($this->post[$index])) return $this->post[$index];

        return false;

    }

    public function get($index = false) {

        if($index === false && isset($this->get)) return $this->get;

        if(isset($this->get[$index])) return $this->get[$index];

        return false;

    }

    public function session($index = false) {

        if($index === false && isset($this->session)) return $this->session;

        if(isset($this->session[$index])) return $this->session[$index];

        return false;

    }

    public function removeSession($index) {

        unset($this->session[$index]);
        unset($_SESSION[$index]);

        return true;

    }

    public function setSession($index = false, $value = false) {

        if((!is_array($index) && $value !== false)
            || (!is_object($index) && $value !== false)) {

            $index = array($index => $value);

        } elseif(is_object($index)) {

            $index = (array) $index;

        } else {

            if(!is_array($index)) throw new \Exception("Invalid where parameters");

        }

        foreach($index as $k => $v) {
            $_SESSION[$k] = $v;
            $this->session = $this->_normalize($_SESSION);
        }

    }

    public function destroySession() {

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

    }

    private function _normalize($data) {

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);

                $data[$this->_normalize($key)] = $this->_normalize($value);
            }
        } else {

            if(is_string($data)) {

//                if(strpos($data, "\r") !== FALSE) {
                $data = trim($data);
//                }

                if(function_exists('iconv') && function_exists('mb_detect_encoding')) {
                    $current_encoding = mb_detect_encoding($data);

                    if($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                        $data = iconv($current_encoding, 'UTF-8', $data);
                    }
                }
                //Global XXS?
                $data = filter_var($data, FILTER_SANITIZE_STRING);

            } elseif(is_numeric($data)) {
                $data = (int) $data;
            }

        }

        return $data;
    }

}
