<?php
namespace core;
use app\modules as modules;

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
        $this->configuration = App::getConfiguration();

        $this->dbh = Database::getInstance()->fetchConnection($name);
    }

    //@TODO: add these to Database, and then set a $this->db here,
    protected function preparePlaceHolders($arguments)
    {
        return str_pad('', count($arguments) * 2 - 1, '?,');
    }

    protected function fetch($sql, $params = [], $returnResultSet = 'result') {
        $result = false;

        try {
            $sth = $this->dbh->prepare($sql);
            $sth->execute($params);
            if($sth->rowCount() > 0) {
                $result = ($sth->rowCount() > 1 || $returnResultSet === 'result' ? $sth->fetchAll(\PDO::FETCH_OBJ) : $sth->fetch(\PDO::FETCH_OBJ));
            }
            $sth->closeCursor();

        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        return $result;
    }

    protected function modify($sql, $params) {
        $result = false;

        try {
            $sth = $this->dbh->prepare($sql);
            $result = $sth->execute($params);
            $sth->closeCursor();
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        return $result;
    }

    protected function lastInsertId()
    {
        return $this->dbh->lastInsertId();
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
                throw new \Exception('Unable to get an instance of the Model class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}