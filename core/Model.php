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
        $this->configuration = App::getConfiguration();
        if($this->configuration->database !== false) {
            $this->database = Database::getInstance();
        }
        if ($this->configuration->cache !== false) {
            $this->cache = new \app\classes\Cache($this->configuration->cache->host, $this->configuration->cache->port);
        }

        $this->load = Load::getInstance();
        $this->request = Request::getInstance();
    }

}
