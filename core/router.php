<?php
namespace core;
use \Exception as Exception;

/**
 * Handles everything relating to URL/URI.
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */

Class Router 
{
    //@TODO: fix these to load from config.. and do some other stuff, no sure yet.


    /**
     * System configuration
     *
     * @var object
     */
    private $_configuration;

    /**
     * Instantiated load class pointer
     *
     * @var object
     * @access private
     */

    private $load;

    /**
     * The active module
     *
     * @var string
     * @access public
     */

    public $module;

    /**
     * The active controller
     *
     * @var string
     * @access public
     */

    public $controller;

    /**
     * The active method
     *
     * @var string
     * @access public
     */

    public $method;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct() {

        $this->_configuration = Registry::get('_configuration');

        $uri = self::uri();

        $this->load = Registry::get('_load');

        if($this->_configuration->routes) {
            if(!empty($this->_configuration->routes[$uri])) {
                $uri = $this->_configuration->routes[$uri];

                $uriChunks = $this->parseURI($uri);

            } elseif(!empty(array_flip($this->_configuration->routes)[$uri])) {
                Router::redirect(Router::baseURL(array_flip($this->_configuration->routes)[$uri]),301);
            }
        }

        if(empty($uriChunks))
            $uriChunks = $this->parseURI($uri);

        Registry::add('_module',$uriChunks[0]);
        Registry::add('_controller', $uriChunks[1]);
        Registry::add('_method',$uriChunks[2]);
        Registry::add('_params', array_slice($uriChunks, 3));


    }


    /**
     * Parse and explode URI segments into chunks
     *
     * @access private
     * @param string $uri
     * @return array chunks of uri
     * @throws Exception on disallowed characters
     */

    private function parseURI($uri) {

        $uriFragments = explode('/', $uri);

        $uriChunks = array();

        foreach($uriFragments as $location => $fragment) {

            $uriChunks[] = $fragment;

        }

        if( !preg_match("/^[a-z0-9:_\/\.\[\]-]+$/i", $uri) ||
            array_filter($uriChunks, function($uriChunk) {
                    if(strpos($uriChunk, '__') !== false) {
                        return true;
                    }
                }
            )
        ){

            throw new Exception('Disallowed key characters.');
        }

        return $uriChunks;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access private
     * @return string formatted/u/r/l
     */

    private static function normalizeURI() {

        if(!empty($_SERVER['PATH_INFO'])) {

            $normalizedURI = $_SERVER['PATH_INFO'];

        } else if(!empty($_SERVER['REQUEST_URI'])) {

            $normalizedURI = $_SERVER['REQUEST_URI'];

        } else {

            $normalizedURI = false;

        }

        if($normalizedURI === '/')
            $normalizedURI = false;


        $normalizedURI = ltrim(preg_replace('/\?.*/', '', $normalizedURI),'/');

        return $normalizedURI;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access public
     * @return string formatted/u/r/l
     */

    public static function uri() {

        $uri = self::normalizeURI();
        $defaultModule = Registry::get('_load')->config('core','modules')['defaultModule'];
        $defaultController = Registry::get('_load')->config('core','modules')[$defaultModule]['defaultController'];
        $defaultMethod = Registry::get('_load')->config('core','modules')[$defaultModule]['defaultMethod'];

        if($uri) {

            $uriChunks = explode('/',filter_var(trim(strtolower($uri)), FILTER_SANITIZE_URL));


            if(!empty($uriChunks[0]) && !empty($uriChunks[1]) && empty($uriChunks[2])) {
                $uriChunks[2] = $defaultMethod;
            }

            if(!empty($uriChunks[0]) && empty($uriChunks[1])) {
                $uriChunks[1] = $defaultController;
                $uriChunks[2] = $defaultMethod;
            }


        } else {

            $uriChunks = array($defaultModule, $defaultController, $defaultMethod);

        }

        $uri = ltrim(implode('/', $uriChunks),'/');

        return $uri;

    }

    /**
     * Return the currentURL w/ query strings
     *
     * @access public
     * @return string http://tld.com/formatted/u/r/l?q=bingo
     */

    public static function currentURL() {

        return self::baseURL().'/'.self::uri().(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');

    }

    /**
     * Return the baseURL
     *
     * @access public
     * @return string http://tld.com
     */

    public static function baseURL($path = '') {

        $self = $_SERVER['PHP_SELF'];
        $server = $_SERVER['HTTP_HOST'].rtrim(str_replace(strstr($self, 'index.php'), '', $self), '/');
        if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {

            $protocol = 'https://';

        } else {

            $protocol = 'http://';

        }

        $url = $protocol.$server;

        if($path !== '')
            $url .= '/'.$path;

        return $url;

    }

    /**
     * Set 401 header, and return noaccess view contents
     *
     * @access public
     * @return string
     */

    public static function showNoAccess($layout) {

        header('HTTP/1.1 401 Access Denied');

        $layout = Registry::get('_load')->view(
            $layout
        );

        return $layout;

    }

    /**
     * Set 404 header, and return 404 view contents
     *
     * @access public
     * @return string
     */

    public static function show404($layout) {


        header('HTTP/1.1 404 Not Found');

        $layout = Registry::get('_load')->view(
            $layout
        );

        return $layout;

    }


    /**
     * Set optional status header, and redirect to provided URL
     *
     * @access public
     * @return bool
     */


    public static function redirect($url = '/', $status = null) {
        $url = str_replace(array('\r','\n','%0d','%0a'), '', $url);
    
        if (headers_sent()) {
            return false;
        }
    
        // trap session vars before redirect
        session_write_close();
    
        if(is_null($status)){
            $status = '302';
        }
        
        // push a status to the browser if necessary
        if ((int)$status > 0) {
            switch($status){
                case '301': $msg = '301 Moved Permanently'; break;
                case '307': $msg = '307 Temporary Redirect'; break;
                // Using these below (except 302) would be an intentional misuse of the 'system'
                case '401': $msg = '401 Access Denied'; break;
                case '403': $msg = '403 Request Forbidden'; break;
                case '404': $msg = '404 Not Found'; break;
                case '405': $msg = '405 Method Not Allowed'; break;
                case '302':
                default: $msg = '302 Found'; break; // temp redirect
            }
            if (isset($msg)) {
                header('HTTP/1.1 '.$msg);
            }
        }
        if (preg_match('/^https?/', $url)) {
            header("Location: $url");
            exit;
        }
        // strip leading slashies
        $url = preg_replace('!^/*!', '', $url);
        header("Location: ".self::baseURL($url));
        exit;

    }
}
