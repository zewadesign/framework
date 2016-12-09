<?php
declare(strict_types=1);
namespace Zewa;

use Sabre\Event\Emitter;
use Zewa\Exception\Exception;
use Zewa\HTTP\Request;

/**
 * This class is the starting point for application
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class App
{
    /**
     * Return value from application
     *
     * @var string
     */
    public $output = '';

    /**
     * @var Dependency $dependency
     */
    private $dependency;

    /**
     * @var Emitter
     */
    private $event;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Request
     */
    private $request;

    /**
     * Application bootstrap process
     *
     * The application registers the configuration in the app/config/core.php
     * and then processes, and makes available the configured resources
     *
     * App constructor.
     * @param Dependency $dependency
     */
    public function __construct(Dependency $dependency)
    {
        $this->configuration = $dependency->resolve('\Zewa\Config');
        $this->event = $dependency->resolve('\Sabre\Event\Emitter', true);
        $this->dependency = $dependency;

        /** @var Security security */
        $this->security = $dependency->resolve('\Zewa\Security', true);
        /** @var Router router */
        $this->router = $dependency->resolve('\Zewa\Router', true);
        /** @var Request request */
        $this->request = $dependency->resolve('\Zewa\HTTP\Request', true);
    }

    /**
     * Calls the proper shell for app execution
     *
     * @access private
     */
    public function initialize()
    {
        $request = $this->router->getAction();

        $isRouteCallback = $this->processRequestParameters($request);

        $this->start($isRouteCallback);

        return $this;
    }

    /**
     * @param $request array
     * @param $request[0] string namespace to load
     * @param $request[1] string method to call
     * @access private
     * @return bool
     * @throws Exception
     */
    private function processRequestParameters($request) : bool
    {
        $params = $this->router->getParameters();

        if ($request !== null) {
            if (is_array($request)) {
                $callback = false;
                $this->request->setRequest($this->dependency->resolve($request[0]));
                $this->request->setMethod(($request[1]??[]));
            } else {
                $callback = true;
                $this->request->setRequest($request);
                array_unshift($params, $this->dependency);
            }
            $this->request->setParams($params);
            return $callback;
        }

        throw new Exception('Invalid request');
    }

    /**
     * @param bool $isRouteCallback
     */
    private function start(bool $isRouteCallback)
    {
        $request = $this->request->getRequest();
        $method = $this->request->getMethod();
        $params = $this->request->getParams();

        if ($isRouteCallback === false) { // Routed Callback
            $this->output = call_user_func_array(
                [&$request, $method],
                $params
            );
        } else {
            $this->output = call_user_func_array($request, $params);
        }
    }

    /**
     * Prepare application return value into a string
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->output;
    }
}
