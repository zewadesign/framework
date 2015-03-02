<?php
namespace core;
use app\modules as modules;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */

abstract class Model {

    /**
     * System configuration
     *
     * @var object
     */

    protected $configuration;

    /**
     * Database object reference
     *
     * @access private
     * @var object
     */

    protected $database;

    /**
     * Instantiated load class pointer
     *
     * @var object
     */

    protected $load;

    /**
     * Instantiated request class pointer
     *
     * @var object
     */

    protected $request;

    /**
     * Cache object reference
     *
     * @access protected
     * @var mixed
     */

    protected $cache = false;

    /**
     * Load up some basic configuration settings.
     */

    public function __construct() {

        $this->configuration = Registry::get('_configuration');
        $this->database = Registry::get('_database');

        if($this->_configuration->cache) {

            $this->cache = Registry::get('_memcached');

        }

        $this->load = Registry::get('_load');
        $this->request = Registry::get('_request');

    }

}