<?php

namespace core;

use app\modules as modules;

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
     * Instantiated load class pointer
     *
     * @var object
     */
    protected $load;

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
     * Instantiated validate class pointer
     *
     * @var object
     */
    protected $validate;

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

    }

    /**
     * @return object
     */
    public function getLoad()
    {
        return $this->load;
    }

    /**
     * @param object $load
     */
    public function setLoad($load)
    {
        $this->load = $load;
    }

    /**
     * @return object
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param object $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return object
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param object $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return object
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param object $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return object
     */
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param object $validate
     */
    public function setValidate($validate)
    {
        $this->validate = $validate;
    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */
    public static function &getInstance()
    {

        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;

    }
}
