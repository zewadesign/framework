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
    protected $dbh;

    /**
     * System configuration
     *
     * @var object
     */
    protected $configuration;

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
        self::$instance = $this;
        // This abstract is strictly to establish inheritance from a global registery.
        $this->configuration = App::getConfiguration();
        if ($this->configuration->cache !== false) {
            $this->cache = new \app\classes\Cache($this->configuration->cache->host, $this->configuration->cache->port);
        }

        $this->dbh = Database::getInstance()->fetchConnection();
    }

    protected function fetch($sql, $params = [], $returnResultSet = 'result') {

        try {
            $result = false;
            $sth = $this->dbh->prepare($sql);
            $sth->execute($params);
            if($sth->rowCount() > 0) {
                $result = ($sth->rowCount() > 1 || $returnResultSet === 'result' ? $sth->fetchAll(\PDO::FETCH_OBJ) : $sth->fetch(\PDO::FETCH_OBJ));
            }
            $sth->closeCursor();
            return $result;

        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

    }

    protected function modify($sql, $params) {
        try {
            $sth = $this->dbh->prepare($sql);
            $result = $sth->execute($params);
            $sth->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }
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
