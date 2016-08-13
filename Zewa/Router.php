<?php
namespace Zewa;

use Zewa\Exception\RouteException;

/**
 * Handles everything relating to URL/URI.
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Router
{
    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $instance = false;

    /**
     * System configuration
     *
     * @var object
     */
    private $configuration;

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
     * The base URL
     * @var string
     * @access public
     */
    public $baseURL;

    /**
     * Default module
     * @var string
     * @access public
     */
    public $defaultModule;

    /**
     * Default controller
     * @var string
     * @access public
     */
    public $defaultController;

    /**
     * Default method
     * @var string
     * @access public
     */
    public $defaultMethod;

    /**
     * Default uri
     * @var string
     * @access public
     */
    public $uri;
    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
        self::$instance = $this;

        $app = App::getInstance();
        $moduleConfig = $app->getConfiguration('modules');

        $this->defaultModule = $moduleConfig->defaultModule;
        $defaultModule = $this->defaultModule;
        $this->defaultController = $moduleConfig->$defaultModule->defaultController;
        $this->defaultMethod = $moduleConfig->$defaultModule->defaultMethod;

        $normalizedURI = $this->normalizeURI();

        //check routes


        $this->uri = $this->uri($normalizedURI);
        $this->baseURL = $this->baseURL();
        $this->currentURL = $this->currentURL();

        //@TODO: routing
        $uriChunks = $this->parseURI($this->uri);

        $app = App::getInstance();
        $app->setConfiguration('router', (object)[
            'module' => $uriChunks[0],
            'controller' => $uriChunks[1],
            'method' => $uriChunks[2],
            'params' => array_slice($uriChunks, 3),
            'baseURL' => $this->baseURL,
            'currentURL' => $this->currentURL
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

    //@TODO add Security class.
    private function normalize($data)
    {
        if (is_numeric($data)) {
            if (is_int($data) || ctype_digit(trim($data, '-'))) {
                $data = (int)$data;
            } elseif ($data === (string)(float)$data) {
                //@TODO: this needs work.. 9E26 converts to float
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
            throw new RouteException('Invalid key characters.');
        }

        return $result;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access private
     * @return string formatted/u/r/l
     */
    private function normalizeURI()
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

        if (! empty($this->configuration->routes)) {
            $normalizedURI = $this->discoverRoute($normalizedURI);
        }
        
        return $normalizedURI;
    }

    private function discoverRoute($uri)
    {
        $routes = $this->configuration->routes;

        foreach ($routes as $route => $reroute) {
            $pattern = '/^(?i)' . str_replace('/', '\/', $route) . '$/';

            if (preg_match($pattern, $uri, $params)) {
                array_shift($params);

                $uri = $reroute;

                if (! empty($params)) {
                    $pat = '/(\$\d+)/';
                    $uri = preg_replace_callback($pat, function () use (&$params) {
                        $first = $params[0];
                        array_shift($params);
                        return $first;
                    }, $reroute);
                }
            }
        }

        return $uri;
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access public
     * @return string formatted/u/r/l
     */
    private function uri($uri)
    {
        if ($uri !== '') {
            $uriChunks = explode('/', filter_var(trim($uri), FILTER_SANITIZE_URL));
            $chunks = $this->sortURISegments($uriChunks);
        } else {
            $chunks = $this->sortURISegments();
        }

        $uri = ltrim(implode('/', $chunks), '/');

        return $uri;
    }

    private function sortURISegments($uriChunks = [])
    {
        $module = ucfirst(strtolower($this->defaultModule));
        $controller = ucfirst(strtolower($this->defaultController));
        $method = ucfirst(strtolower($this->defaultMethod));

        if (!empty($uriChunks)) {
            $module = ucfirst(strtolower($uriChunks[0]));

            if (!empty($uriChunks[1])) {
                $controller = ucfirst(strtolower($uriChunks[1]));
            } elseif (!empty($this->configuration->modules->$module->defaultController)) {
                $controller = $this->configuration->modules->$module->defaultController;
            }

            if (!empty($uriChunks[2])) {
                $method = ucfirst(strtolower($uriChunks[2]));
                $class = '\\App\\Modules\\' . $module . '\\Controllers\\' . $controller;
                $methodExist = method_exists($class, $method);
                
                if ($methodExist === false) {
                    if (!empty($this->configuration->modules->$module->defaultMethod)) {
                        $method = $this->configuration->modules->$module->defaultMethod;
                        array_unshift($uriChunks, null);
                    }
                }
            } elseif (!empty($this->configuration->modules->$module->defaultMethod)) {
                $method = $this->configuration->modules->$module->defaultMethod;
            }

            unset($uriChunks[0], $uriChunks[1], $uriChunks[2]);
        }

        $return = [$module, $controller, $method];
        return array_merge($return, array_values($uriChunks));
    }

    private function addQueryString($url, $key, $value)
    {
        $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        if (strpos($url, '?') === false) {
            return ($url . '?' . $key . '=' . $value);
        } else {
            return ($url . '&' . $key . '=' . $value);
        }
    }

    private function removeQueryString($url, $key)
    {
        $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        return ($url);
    }

    /**
     * Return the currentURL w/ query strings
     *
     * @access public
     * @return string http://tld.com/formatted/u/r/l?q=bingo
     */
    public function currentURL($params = false)
    {
        if (trim($_SERVER['REQUEST_URI']) === '/') {
            $url = $this->baseURL()
                   . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        } else {
            $url = $this->baseURL($this->uri)
                   . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        }

        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $url = $this->removeQueryString($url, $key);
                $url = $this->addQueryString($url, $key, $param);
            }
        }

        return $url;
    }

    /**
     * Return the baseURL
     *
     * @access public
     * @return string http://tld.com
     */
    public function baseURL($path = '')
    {
        if (is_null($this->baseURL)) {
            $self = $_SERVER['PHP_SELF'];
            $server = $_SERVER['HTTP_HOST']
                      . rtrim(str_replace(strstr($self, 'index.php'), '', $self), '/');

            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
                || !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                   && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
            ) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }

            $this->baseURL = $protocol . $server;
        }

        $url = $this->baseURL;

        if ($path !== '') {
            $url .= '/' . $path;
        }

        return $url;
    }

    /**
     * Set optional status header, and redirect to provided URL
     *
     * @access public
     * @return bool
     */
    public function redirect($url = '/', $status = null)
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
            //@TODO: does this break without exit?
            //exit;
        }
        // strip leading slashies
        $url = preg_replace('!^/*!', '', $url);
        header("Location: " . $this->baseURL($url));
        //@TODO: does this break without exit?
        //exit;
    }
}
