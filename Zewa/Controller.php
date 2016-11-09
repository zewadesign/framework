<?php

namespace Zewa;

use Zewa\Interfaces\ContainerInterface;

use Zewa\Interfaces\ContainerInterface;

/**
 * Abstract class for controller extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
//can name spaces be removed in the classes extending..?
abstract class Controller
{
    /**
     * System configuration
     *
     * @var Config
     */
    protected $configuration;

    /**
     * Instantiated router class pointer
     *
     * @var Router
     */
    protected $router;

    /**
     * Instantiated request class pointer
     *
     * @var Request
     */
    protected $request;

    /**
     * League\Container
     *
     * @var DIContainer
     */
    protected $container;

    /**
     * Instantiated output class pointer
     *
     * @var object
     */
    protected $output;

    /**
     * @var View
     */
    protected $view;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
    }

    public function setView(View $view)
    {
        $this->view = $view;
        //@TODO instead of "getview" this needs to be "setResponse" -- set response should receive a view,
        //views probably don't need config, router, or request -- but they need access
        //to methods inside of there.
        // I'm not sure how I want to handle this yet.
//        return new View($this->configuration, $this->router, $this->request);
    }

    public function setConfig(Config $config)
    {
        $this->configuration = $config;
    }

    public function getConfig() : Config
    {
        return $this->configuration;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function getRouter() : Router
    {
        return $this->router;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest() : Request
    {
        return $this->request;
    }

    public function setContainer(DIContainer $container)
    {
        $this->container = $container;
    }

    public function getContainer() : DIContainer
    {
        return $this->container;
    }
}
