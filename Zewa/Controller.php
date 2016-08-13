<?php

namespace Zewa;

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
     * @var object
     */
    protected $configuration;

    /**
     * Instantiated router class pointer
     *
     * @var object
     */
    protected $router;

    /**
     * Instantiated request class pointer
     *
     * @var object
     */
    protected $request;

    /**
     * Instantiated output class pointer
     *
     * @var object
     */
    protected $output;

    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    public static $instance;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
        static::$instance = $this;

        $app = App::getInstance();
        $this->configuration = $app->getConfiguration();
        
        $this->request = App::getService('request');
        $this->router = App::getService('router');
    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     * @throws Exception\TypeException
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            throw new Exception\TypeException('There is no instance of ACL available.');
        }

        return static::$instance;
    }
}
