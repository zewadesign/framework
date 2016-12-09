<?php
declare(strict_types=1);
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
     * System routes
     *
     * @var object
     */
    private $routes;

    /**
     * The base URL
     *
     * @var    string
     * @access public
     */
    public $baseURL;

    public $currentURL;

    /**
     * Default uri
     *
     * @var    string
     * @access public
     */
    public $uri;

    /**
     * @var array
     */
    public $params = [];

    public $action;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct(Config $config)
    {
        $this->routes = $config->get('Routes');
        $this->prepare();
    }

    /**
     * Set class defaults and normalized url/uri segments
     */
    private function prepare()
    {
        $this->uri = $this->getURI();
        $this->discoverRoute();
        $this->isURIClean();
        $this->currentURL = $this->baseURL($this->uri);
        $this->baseURL = $this->baseURL();
    }

    /**
     * Checks if URL contains special characters not permissable/considered dangerous
     *
     * Safe: a-z, 0-9, :, _, [, ], +
     * @throws RouteException
     */
    private function isURIClean()
    {
        if ($this->uri !== '' && !preg_match("/^[a-z0-9:_\/\.\[\]-]+$/i", $this->uri)) {
            throw new RouteException('Disallowed characters');
        }
    }

    /**
     * Normalize the $_SERVER vars for formatting the URI.
     *
     * @access private
     * @return string formatted/u/r/l
     */
    private function getURI()
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = false;
        }

        if ($uri === '/') {
            $uri = false;
        }

        return trim(preg_replace('/\?.*/', '', $uri), '/');
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParameters()
    {
        return $this->params;
    }

    //@TODO Normalize parameters.
    private function discoverRoute()
    {
        $routes = $this->routes;
        $params = [];


        foreach ($routes as $route => $action) {
            $pattern = '/^(?i)' . str_replace('/', '\/', $route) . '$/';
            // normalize these parameters
            if (preg_match($pattern, $this->uri, $params)) {
                array_shift($params);
                $this->action = $action;
                $this->params = $params;
            }
        }
    }

    public function addQueryString($url, $key, $value)
    {
        $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        if (strpos($url, '?') === false) {
            return ($url . '?' . $key . '=' . $value);
        } else {
            return ($url . '&' . $key . '=' . $value);
        }
    }

    public function removeQueryString($url, $key)
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
    public function currentURL()
    {
        $queryString = empty($_SERVER['QUERY_STRING']) === true ? "" : '?' . $_SERVER['QUERY_STRING'];
        return $this->currentURL . $queryString;
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
}
