<?php
namespace core;

use \Exception as Exception;

/**
 * Handles everything relating to URL/URI.
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Router
{
    /**
     * System configuration
     *
     * @var object
     */
    private $configuration;

    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $baseURL = false;

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
    public function __construct()
    {
        $this->configuration = App::getConfiguration();
        $uri = self::uri();

        //@TODO: fix routes..
//        if ($this->configuration->routes) {
//            if (!empty($this->configuration->routes->$uri)) {
//                $uri = $this->configuration->routes->$uri;
//                $uriChunks = $this->parseURI($uri);
//            } elseif (!empty(array_flip((array)$this->configuration->routes)[$uri])) {
//                Router::redirect(Router::baseURL(array_flip((array)$this->configuration->routes)[$uri]), 301);
//            }
//        }

        if (empty($uriChunks)) {
            $uriChunks = $this->parseURI($uri);
        }

        App::setConfiguration('router', (object)[
            'module' => $uriChunks[0],
            'controller' => $uriChunks[1],
            'method' => $uriChunks[2],
            'params' => array_slice($uriChunks, 3),
            'baseURL' => self::baseURL(),
            'currentURL' => self::currentURL()
        ]);

    }


    private function isURIClean($uri, $uriChunks)
    {
        if (!preg_match("/^[a-z0-9:_\/\.\[\]-]+$/i", $uri)
            || array_filter($uriChunks, function ($uriChunk) {
                if (strpos($uriChunk, '__') !== false) {
                    return true;
                }
            })
        ) {
            return false;
        } else {
            return true;
        }
    }

    private function normalize($data)
    {
        if (is_numeric($data)) {
            if (is_int($data) || ctype_digit(trim($data, '-'))) {
                $data = (int)$data;
            } else if ($data == (string)(float)$data) {
                $data = (float)$data;
            }
        }
        return $data;
    }

    /**
     * Parse and explode URI segments into chunks
     *
     * @access private
     *
     * @param string $uri
     *
     * @return array chunks of uri
     * @throws Exception on disallowed characters
     */
    private function parseURI($uri)
    {
        $uriFragments = explode('/', $uri);
        $uriChunks = [];
        $params = [];
        $iteration = 0;
        foreach ($uriFragments as $location => $fragment) {
            if ($iteration > 2) {
                $params[] = $this->normalize(trim($fragment));
            } else {
                $uriChunks[] = trim($fragment);
            }
            $iteration++;
        }

        $result = array_merge($uriChunks, $params);

        if ($this->isURIClean($uri, $result) === false) {
            die('Invalid key characters.');
        }

        return $result;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access private
     * @return string formatted/u/r/l
     */
    private static function normalizeURI()
    {

        if (!empty($_SERVER['PATH_INFO'])) {
            $normalizedURI = $_SERVER['PATH_INFO'];

        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $normalizedURI = $_SERVER['REQUEST_URI'];

        } else {
            $normalizedURI = false;

        }

        if ($normalizedURI === '/') {
            $normalizedURI = false;
        }

        $normalizedURI = ltrim(preg_replace('/\?.*/', '', $normalizedURI), '/');

        return $normalizedURI;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access public
     * @return string formatted/u/r/l
     */
    public static function uri()
    {

        $load = Load::getInstance();
        $uri = self::normalizeURI();

        $defaultModule = $load->config('core', 'modules')->defaultModule;
        $defaultController = $load->config('core', 'modules')->$defaultModule->defaultController;
        $defaultMethod = $load->config('core', 'modules')->$defaultModule->defaultMethod;

        $module = $defaultModule;
        $controller = $defaultController;
        $method = $defaultMethod;
        $arguments = [];

        if ($uri) {
            $uriChunks = explode('/', filter_var(trim(strtolower($uri)), FILTER_SANITIZE_URL));

            if (!empty($uriChunks)) {
                $module = $uriChunks[0];
                $moduleConfig = $load->config('core', 'modules');

                if (!empty($uriChunks[1])) { // && !empty($moduleConfig[$module]['defaultController'])) {
                    $controller = $uriChunks[1];
                } else if (!empty($moduleConfig->$module->defaultController)) {
                    $controller = $moduleConfig->$module->defaultController;
                }

                if (!empty($uriChunks[2])) {
                    $method = $uriChunks[2];
                } else if (!empty($moduleConfig->$module->defaultMethod)) {
                    $method = $moduleConfig->$module->defaultMethod;
                }

                unset($uriChunks[0]);
                unset($uriChunks[1]);
                unset($uriChunks[2]);

                if (!empty($uriChunks[3])) {
                    foreach ($uriChunks as $c) {
                        $arguments[] = $c;
                    }
                }
            }
        }

        $chunks = [$module, $controller, $method];
        if (!empty($arguments)) {
            $chunks = array_merge($chunks, $arguments);
        }
        $uri = ltrim(implode('/', $chunks), '/');
        return $uri;

    }

    /**
     * Return the currentURL w/ query strings
     *
     * @access public
     * @return string http://tld.com/formatted/u/r/l?q=bingo
     */
    public static function currentURL()
    {

        return self::baseURL() . '/' . self::uri() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

    }

    /**
     * Return the baseURL
     *
     * @access public
     * @return string http://tld.com
     */
    public static function baseURL($path = '')
    {
        if (self::$baseURL !== false) {
            return self::$baseURL;
        }

        $self = $_SERVER['PHP_SELF'];
        $server = $_SERVER['HTTP_HOST'] . rtrim(str_replace(strstr($self, 'index.php'), '', $self), '/');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';

        }

        $url = $protocol . $server;

        if ($path !== '') {
            $url .= '/' . $path;
        }

        return $url;

    }

    /**
     * Set 401 header, and return noaccess view contents
     *
     * @access public
     * @return string
     */
    public static function showNoAccess($data)
    {
        header('HTTP/1.1 401 Access Denied');
        $view = new View;
        $view->setProperty($data);
        $view->setLayout('no-access');
        return $view->render();
    }

    /**
     * Set 404 header, and return 404 view contents
     *
     * @access public
     * @param $module string
     * @param $data array
     * @return string
     */
    public static function show404($data = [])
    {
        header('HTTP/1.1 404 Not Found');
        $view = new View;
        $view->setProperty($data);
        $view->setLayout('404');
        return $view->render();
    }

    /**
     * Set optional status header, and redirect to provided URL
     *
     * @access public
     * @return bool
     */
    public static function redirect($url = '/', $status = null)
    {
        $url = str_replace(array('\r', '\n', '%0d', '%0a'), '', $url);

        if (headers_sent()) {
            return false;
        }

        // trap session vars before redirect
        session_write_close();

        if (is_null($status)) {
            $status = '302';
        }

        // push a status to the browser if necessary
        if ((int)$status > 0) {
            switch ($status) {
                case '301':
                    $msg = '301 Moved Permanently';
                    break;
                case '307':
                    $msg = '307 Temporary Redirect';
                    break;
                // Using these below (except 302) would be an intentional misuse of the 'system'
                case '401':
                    $msg = '401 Access Denied';
                    break;
                case '403':
                    $msg = '403 Request Forbidden';
                    break;
                case '404':
                    $msg = '404 Not Found';
                    break;
                case '405':
                    $msg = '405 Method Not Allowed';
                    break;
                case '302':
                default:
                    $msg = '302 Found';
                    break; // temp redirect
            }
            if (isset($msg)) {
                header('HTTP/1.1 ' . $msg);
            }
        }
        if (preg_match('/^https?/', $url)) {
            header("Location: $url");
            exit;
        }
        // strip leading slashies
        $url = preg_replace('!^/*!', '', $url);
        header("Location: " . self::baseURL($url));
        exit;

    }
}
