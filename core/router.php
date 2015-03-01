<?php
//depends on the load object, passed by reference
namespace core;

Class Router 
{
    //@TODO: fix these to load from config.. and do some other stuff, no sure yet.

    private $loader;
    public $module;
    public $controller;
    public $method;
    public $url = array();

    
    function __construct() {

        $uri = self::uri();
        $this->loader = Registry::get('_loader');

        /*
         * @TODO: implemnet routing
         *
         */
        $uriFragments = explode('/', $uri);

        $uriChunks = array();

        foreach($uriFragments as $location => $fragment) {

            $uriChunks[] = $fragment;

        }

        Registry::add('rootPath', ROOT_PATH);
        Registry::add('baseURL', self::baseURL());
        Registry::add('uri', $this->uri());
        Registry::add('currentURL', $this->url());
        Registry::add('_params', array_slice($uriChunks, 3));

        if( !preg_match("/^[a-z0-9:_\/\.\[\]-]+$/i", $uri) ||
            array_filter($uriChunks, function($uriChunk) {
                    if(strpos($uriChunk, '__') !== false) {
                        return true;
                    }
                }
            )
        ){

            throw new \Exception('Disallowed key characters.');
        }

    }

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

    public static function uri() {

        $uri = self::normalizeURI();
        $defaultModule = Registry::get('_loader')->config('core','modules')['defaultModule'];
        $defaultController = Registry::get('_loader')->config('core','modules')[$defaultModule]['defaultController'];
        $defaultMethod = Registry::get('_loader')->config('core','modules')[$defaultModule]['defaultMethod'];

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

        Registry::add('_module',$uriChunks[0]);
        Registry::add('_controller', $uriChunks[1]);
        Registry::add('_method',$uriChunks[2]);
        $uri = ltrim(implode('/', $uriChunks),'/');

        return $uri;

    }

    public static function url() {

        return self::baseURL().'/'.self::uri().(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');

    }
    
    public static function baseURL() {

        $self = $_SERVER['PHP_SELF'];
        $server = $_SERVER['HTTP_HOST'].rtrim(str_replace(strstr($self, 'index.php'), '', $self), '/');
        if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {

            $protocol = 'https://';

        } else {

            $protocol = 'http://';

        }

        return $protocol.$server;

    }

    public static function showNoAccess($layout) {

        header('HTTP/1.1 401 Access Denied');

        $layout = Registry::get('_loader')->view(
            $layout
        );

        return $layout;

    }

    public static function show404($layout) {


        header('HTTP/1.1 404 Not Found');

        $layout = Registry::get('_loader')->view(
            $layout
        );

        return $layout;

    }

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
        header("Location: ".self::baseURL().'/'.$url);
        exit;
    }
}
