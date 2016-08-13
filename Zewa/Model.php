<?php
namespace Zewa;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Model
{
    /**
     * Database object reference
     *
     * @access private
     * @var object
     */
    protected $dbh;

    /**
     * System configuration
     *
     * @var object
     */
    protected $configuration;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct($name = 'default')
    {
        // This abstract is strictly to establish inheritance from a global registery.
        $app                 = App::getInstance();
        $this->configuration = $app->getConfiguration();
        if ($this->configuration->database !== false) {
            $database  = $app->getService('database');
            $this->dbh = $database->fetchConnection($name);
        }
    }

    //@TODO: add these to Database, and then set a $this->db here,
    protected function preparePlaceHolders($arguments)
    {
        return str_pad('', count($arguments) * 2 - 1, '?,');
    }

    protected function fetch($sql, $params = [], $returnResultSet = 'result')
    {
        $result = false;

        if (is_null($this->dbh)) {
            throw new \PDOException('Database handler is not available');
        }

        $this->sth = $this->dbh->prepare($sql);
        $this->sth->execute($params);

        if ($this->sth->rowCount() > 0) {
            if ($this->sth->rowCount() > 1 || $returnResultSet === 'result') {
                $result = $this->sth->fetchAll(\PDO::FETCH_OBJ);
            } else {
                $result = $this->sth->fetch(\PDO::FETCH_OBJ);
            }
        }

        $this->sth->closeCursor();

        return $result;
    }

    protected function modify($sql, $params)
    {
        $result = false;

        if (is_null($this->dbh)) {
            throw new \PDOException('Database handler is not available');
        }
        $this->sth = $this->dbh->prepare($sql);
        $result    = $this->sth->execute($params);
        $this->sth->closeCursor();

        return $result;
    }

    protected function lastInsertId()
    {
        if (is_null($this->dbh)) {
            throw new \PDOException('Database handler is not available');
        }

        return $this->dbh->lastInsertId();
    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function getInstance()
    {
        if (self::$instance === null) {
            throw new Exception\TypeException('There is no instance of Model available.');
        }

        return self::$instance;
    }
}
