<?php
namespace core;

use app\modules as modules;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
abstract class Model
{
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
    public function __construct()
    {
        // This abstract is strictly to establish inheritance from a global registery.
        $this->configuration = Registry::get('_configuration');
        $this->database = Registry::get('_database');

        if ($this->_configuration->cache) {
            $this->cache = Registry::get('_memcached');

        }

        $this->load = Registry::get('_load');
        $this->request = Registry::get('_request');
    }

    /**
     * @param object $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param object $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @param object $load
     */
    public function setLoad($load)
    {
        $this->load = $load;
    }

    /**
     * @param object $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param mixed $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }
}