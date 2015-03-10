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
        $this->configuration = App::getConfiguration();
        $this->load = Load::getInstance();
        $this->request = Request::getInstance();
    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */
    public static function &getInstance()
    {

        try {

            if (static::$instance === null) {
                throw new Exception('Unable to get an instance of the controller class. The class has not been instantiated yet.');
            }

            return static::$instance;

        } catch(Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}
