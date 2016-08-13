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

        $app = App::getInstance();
        $this->configuration = $app->getConfiguration('database');

        $this->establishConnection($name);
    }

    public function establishConnection($name = 'default')
    {
        if ($this->configuration !== false) {
            if (! empty ( $this->configuration->$name )) {
                $dbConfig = $this->configuration->$name;
                self::$dbh[$name] = new \PDO($dbConfig->dsn, $dbConfig->user, $dbConfig->pass);
                self::$dbh[$name]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } else {
                throw new \PDOException(
                    'Please specify a valid database configuration, or provide a default configuration.'
                );
            }

        }

    }

    public function fetchConnection($name = 'default')
    {
        if (!empty(self::$dbh[$name])) {
            return self::$dbh[$name];
        } elseif (!empty($this->configuration->$name)) {
            $this->establishConnection($name);
            return self::$dbh[$name];
        } else {
            throw new Exception\LookupException('Cannot find a valid database config for: ' . $name);
        }
    }
}
