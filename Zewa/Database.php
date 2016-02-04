<?php

namespace Zewa;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Database
{
    private $configuration;

    /**
     * Database object reference
     *
     * @access private
     * @var object
     */
    protected static $dbh = [];

    public function __construct($name = 'default')
    {
        $this->configuration = App::getConfiguration('database');
        $this->establishConnection($name);
    }

    public function establishConnection($name = 'default')
    {
        try {

            if($this->configuration !== false) {

                if( ! empty ( $this->configuration->$name ) ) {
                    $dbConfig = $this->configuration->$name;
                    self::$dbh[$name] = new \PDO($dbConfig->dsn, $dbConfig->user, $dbConfig->pass);
                    self::$dbh[$name]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                } else {
                    throw new \PDOException('Please specify a valid database configuration, or provide a default configuration.');
                }

            }

        } catch (\PDOException $e) {
            echo "<strong>TypeException:</strong> <br/>";
            echo $e->getMessage();
            exit;
        }

    }

    public function fetchConnection($name = 'default')
    {
        try {
            if (!empty(self::$dbh[$name])) {
                return self::$dbh[$name];
            } else if (!empty($this->configuration->$name)) {
                $this->establishConnection($name);
                return self::$dbh[$name];
            } else {
                throw new Exception\LookupException('Cannot find a valid database config for: ' . $name);
            }
        } catch(Exception\LookupException $e) {
            echo "<strong>LookupException:</strong> <br/>";
            echo $e->getMessage();
            exit;
        }
    }
}
