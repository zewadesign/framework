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
        $app = App::getInstance();
        $this->configuration = $app->getConfiguration();
        if ($this->configuration->database !== false) {
            $database = App::getService('database');
            $this->dbh = $database->fetchConnection($name);
        }
    }
    //@TODO: add these to Database, and then set a $this->db here,
    protected function preparePlaceHolders($arguments)
    {
        return str_pad('', count($arguments) * 2 - 1, '?,');
    }

    protected function fetch($sql, $params = [], $returnResultSet = 'result') {
        $result = false;

        try {
            if(is_null($this->dbh)) {
                throw new \PDOException('Database handler is not available');
            }
            $this->sth = $this->dbh->prepare($sql);
            $this->sth->execute($params);
            if($this->sth->rowCount() > 0) {
                $result = ($this->sth->rowCount() > 1 || $returnResultSet === 'result' ? $this->sth->fetchAll(\PDO::FETCH_OBJ) : $this->sth->fetch(\PDO::FETCH_OBJ));
            }
            $this->sth->closeCursor();

        } catch (\PDOException $e) {

            echo "<strong>PDOException:</strong> <br/>";
            echo $e->getMessage();
            exit;

        }

        return $result;
    }

    protected function modify($sql, $params) {
        $result = false;

        try {
            if(is_null($this->dbh)) {
                throw new \PDOException('Database handler is not available');
            }
            $this->sth = $this->dbh->prepare($sql);
            $result = $this->sth->execute($params);
            $this->sth->closeCursor();
        } catch (\PDOException $e) {

            echo "<strong>PDOException:</strong> <br/>";
            echo $e->getMessage();
            exit;

        }

        return $result;
    }

    protected function lastInsertId()
    {
        try {
            if(is_null($this->dbh)) {
                throw new \PDOException('Database handler is not available');
            }
            return $this->dbh->lastInsertId();
        } catch (\PDOException $e) {

            echo "<strong>PDOException:</strong> <br/>";
            echo $e->getMessage();
            exit;

        }
    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function getInstance()
    {

        try {

            if (self::$instance === null) {
                throw new Exception\TypeException('There is no instance of Model available.');
            }

            return self::$instance;

        } catch(Exception\TypeException $e) {
            echo "<strong>TypeException:</strong> <br/>";
            echo $e->getMessage();
            exit;
        }

    }
}
