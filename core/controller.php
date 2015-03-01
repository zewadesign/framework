<?php

namespace core;
use app\modules as modules;

/**
 * Abstract class for controller extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */

//can name spaces be removed in the classes extending..?
abstract class Controller {

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

    public function __construct() {

        $this->router = Registry::get('_router');
        $this->load = Registry::get('_load');
        $this->request = Registry::get('_request');
        $this->output = Registry::get('_output');
        $this->validate = Registry::get('_validate');


    }

    /**
     * Load up some basic configuration settings.
     *
     * @access public
     * @return object a reference of the controller
     */

    public static function &getInstance() {

        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;

    }

}