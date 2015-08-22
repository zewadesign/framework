<?php
namespace core;
use app\modules as modules;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Database
{
    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $instance;

    /**
     * Database object reference
     *
     * @access private
     * @var object
     */
    protected $dbh = [];

    /**
     * Database configurations
     *
     * @access private
     * @var object
     */
    private $config;



    /**
     * Load up some basic configuration settings.
     */
    public function __construct($config)
    {
        self::$instance = $this;
        $this->config = $config;
        $this->establishConnection($config->default);
    }

    public function establishConnection($config, $name = 'default')
    {
        if($config !== false) {
            try {
                $this->dbh[$name] = new \PDO($config->dsn, $config->user, $config->pass);
                $this->dbh[$name]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\Exception $e) {
                // Echo a different exception here.
                echo $e->getMessage();
            }

        } else {
            throw new \Exception('Please specify a valid database configuration.');
        }
    }

    public function fetchConnection($name = 'default')
    {
        if( ! empty($this->dbh[$name]) ) {
            return $this->dbh[$name];
        } else if( ! empty($this->config->$name) ){
            $this->establishConnection($this->config->$name, $name);
            return $this->dbh[$name];
        }

        return false;
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

            if (self::$instance === null) {
                throw new \Exception('Unable to get an instance of the database class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            echo '<strong>Message:</strong> ' . $e->getMessage();

        }

    }
}
