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
         * if ($routes = $this->load->config('routes')) {
         *    $params = array();
         *    foreach ($routes as $k=>$v) {
         *        $params[] = preg_replace('#^'.$k.'$#', $v, $uri);
         *    }
         *    if ($params) {
         *        $uri = trim(implode('/', array_filter($params)), '/');
         *        unset($params);
         *    }
         * }
         *
         *
         * unset($routes);
         */
        $uriFragments = explode('/', $uri);

        $this->url['segments'] = array();

        foreach($uriFragments as $location => $fragment) {

            if (strpos($fragment, '__') !== false) {
                //strip those nasty magic methods.. nice try XD
                $fragment = preg_replace("/^_+[^a-z]/", "", $fragment);
            }

            switch($location) {

                case 0:
                    $this->module = $fragment;
                break;
                case 1:
                    $this->controller = $fragment;
                break;
                case 2:
                    $this->method = $fragment;
                break;

            }

            $this->url['segments'][] = $fragment;

        }

        $this->url['base_url'] = self::baseURL();

        $this->url['uri'] = implode('/', $this->url['segments']);

        if (!preg_match("/^[a-z0-9:_\/\.\[\]-]+$/i", $this->url['uri'])) {
            exit('Disallowed key characters.');
        }

    }


    public static function uri() {

	if(!empty($_SERVER['PATH_INFO'])) {
		$path = $_SERVER['PATH_INFO'];
	} else if(!empty($_SERVER['REQUEST_URI'])) {
		$path = $_SERVER['REQUEST_URI'];
	} else {
		$path = false;
	}
	if($path === '/')
		$path = false;

        if($path) { //@TODO: fix global access
            $path = preg_replace('/\?.*/', '', $path);
            $uri = explode('/',filter_var(trim(strtolower($path)), FILTER_SANITIZE_URL));

            if(!empty($uri[1]) && !empty($uri[2]) && empty($uri[3])) {
                $uri[3] = 'index';
            }

        } else {

            $defaultModule = Registry::get('_loader')->config('core','modules')['defaultModule'];
            $defaultController = Registry::get('_loader')->config('core','modules')[$defaultModule]['defaultController'];
            $defaultMethod = Registry::get('_loader')->config('core','modules')[$defaultModule]['defaultMethod'];
            $uri = array($defaultModule, $defaultController, $defaultMethod);

        }

        $uri = ltrim(implode('/', $uri),'/');

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
