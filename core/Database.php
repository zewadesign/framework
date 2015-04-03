<?php
namespace core;
use app\modules as modules;

/**
 * Database PDO DAL
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 * @TODO: add USE Schema query
 *
 * @TODO: insert transactions / roll backs
 * @TODO: convert syntax to ? placeholders ??
 * @TODO: put everything in transactional SQL commands
 */
class Database
{
    /**
     * System configuration
     *
     * @var object
     */
    private $configuration;

    /**
     * Reference to instantiated database object.
     *
     * @var object
     */
    protected static $instance;

    /**
     * Database handler
     *
     * @var object
     */
    private $dbh;

    /**
     * DSN store
     *
     * @var array
     */
    private $dbhStore = [];

    /**
     * Current call JOIN
     *
     * @var string
     */
    private $join = '';

    /**
     * Current call GRPI{ NU
     *
     * @var string
     */
    private $groupBy = '';

    /**
     * Current call WHERE
     *
     * @var string
     */
    private $where = '';

    /**
     * Current call OR WHERE
     *
     * @var string
     */
    private $orWhere = '';

    /**
     * Current call TYPED WHERE
     * @TODO: remove??
     * @var string
     */
//    private $typedWhere = false;

    /**
     * Current call WHERE BETWEEN
     *
     * @var string
     */
    private $whereBetween = '';

    /**
     * Current call WHERE IN
     *
     * @TODO camelCase
     * @var string
     */
    private $where_in = '';

    /**
     * Current call WHERE LIKE
     *
     * @var string
     */
    private $whereLike = '';

    /**
     * Current call WHERE NOT IN
     *
     * @var string
     */
    private $whereNotIn = '';

    /**
     * Current call FROM TABLE
     *
     * @var string
     */
    private $table;

    /**
     * Current call SELECT columns
     *
     * @var string
     */
    private $columns = '*';

    /**
     * Current call ORDER BY
     *
     * @var string
     */
    private $orderBy = '';

    /**
     * Current call LIMIT
     *
     * @var string
     */
    private $limit;

    /**
     * Prepared statement parameters in order
     *
     * @var array
     */
    private $arguments = [];

    /**
     * Current call JOIN
     *
     * @TODO update ?
     * @var string
     */
    private $whereKeyValues = [];

    /**
     * Current call JOIN
     *
     * @TODO update ?
     * @var string
     */
    private $orWhereKeyValues = array();

    /**
     * Grab application configuration, set active DSN
     *
     * @param bool $name core config index
     * @TODO: pass default connection info, rather then index, to decouple from framework
     */
    public function __construct($name = false)
    {

        $this->configuration = App::getConfiguration();

        self::$instance = $this;

        if($name !== false) {
            $this->setDSN($name);
        } else {
            $this->setDSN('default');
        }

    }

    /**
     * Set the active DSN (from available app core configuration)
     *
     * @param $name
     */
    public function setDSN($name)
    {

        try {
            if (!empty($this->dbhStore[$name])) {
                $this->dbh = $this->dbhStore[$name];
            } else {
                if (empty($this->configuration->database->$name)) {
                    throw new \Exception('Please specify connection parameters for: ' . $name . ' in the configuration file.');
                }

                $config = $this->configuration->database->$name;

                $this->dbh = new \PDO($config->dsn, $config->user, $config->pass);
                $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $this->dbhStore[$name] = $this->dbh;

            }

        } catch (\Exception $e) {
            // Echo a different exception here.
            echo $e->getMessage();

        }
    }

    /**
     * Close statement
     *
     * @param $sth
     */
    private function close($sth)
    {
        $sth->closeCursor();
        $this->join = '';
        $this->groupBy = false;
        $this->where = false;
        $this->orWhere = false;
        $this->typedWhere = false;
        $this->whereBetween = false;
        $this->where_in = false;
        $this->whereLike = false;
        $this->whereNotIn = false;
        $this->table = false;
        $this->columns = '*';
        $this->orderBy = false;
        $this->limit = false;
        $this->arguments = array();
        $this->whereKeyValues = array();
        $this->orWhereKeyValues = array();

    }

    /**
     * Generate GROUP BY statement
     *
     * @param mixed $column
     * @return $this
     * @throws \Exception
     */
    public function groupBy($column = false)
    {

        if ($column === false) {
            throw new \Exception('Please specify a column for the group by.');
        }

        $this->groupBy = ' GROUP BY ' . $column;

        return $this;

    }

    /**
     * Construct WHERE portion of SQL query from properties
     *
     * @return string
     */
    private function prepareWhere()
    {

        $formattedWhere = '';

        if ($this->where_in) {
            $formattedWhere .= $this->where_in;
        }

        if ($this->whereLike) {
            $formattedWhere .= $this->whereLike;
        }
        if ($this->where) {
            if ($this->where_in || $this->whereLike) {
                $formattedWhere .= 'AND ';
            }

            $formattedWhere .= ($this->orWhere) ? '(' . $this->where . ')' : $this->where;

            //or where here, because if there is no WHERE, there is no OR. :)
            if ($this->orWhere) {
                $formattedWhere .= ' OR ( ' . $this->orWhere . ' ) ';
            }

        }


        if ($this->typedWhere) {
            $formattedWhere .= $this->typedWhere;
        }

        if ($this->whereBetween) {
            if (strlen($formattedWhere) > 0) {
                $formattedWhere .= ' AND ' . $this->whereBetween;
            } else {
                $formattedWhere = $this->whereBetween;
            }
        }

        if (strlen($formattedWhere) > 0) {
            $formattedWhere = ' WHERE ' . $formattedWhere;
        }

        return $formattedWhere;
    }

    /**
     * Returns true if value contains SQL keywords
     *
     * @param $str
     * @return bool
     */
    private function hasOperator($str)
    {

        return (bool) preg_match(
            '/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim($str)
        );

    }

    /**
     * Normalize provided WHERE parameters, and their order
     *
     * @param $field
     * @param bool $value
     * @return array
     * @throws \Exception
     */
    public function whereParamToArray($field, $value = false)
    {

        if ((!is_array($field) && $value !== false)
            || (!is_object($field) && $value !== false)
        ) {
            if (is_string($field) && is_array($value)) {
                $field = $value;
            } else {
                $field = array($field => $value);
            }


        } elseif (is_object($field)) {
            $field = (array) $field;

        } else {
            if (!is_array($field)) {
                throw new \Exception("Invalid where parameters");
            }

        }

        return $field;

    }

    /**
     * Generate WHERE prepared statement
     *
     * @param $values
     * @return bool|string
     * @TODO: add extension for tools that extract plaintext queries into prepared statements & binding
     */
    private function prepareWhereValues($values)
    {

        $where = '';


        $f = array_keys($values);

        $a = end($f);

        foreach ($f as $b) {
            if ($a == $b) {
                if ($this->hasOperator($b)) {
                    if (strstr(strtolower($b), 'is null') !== false) {
                        $where .= $b;

                    } else {
                        $where .= $b . ' ?';

                    }
                } else {
                    $where .= $b . ' = ?';

                }

            } else {
                if ($this->hasOperator($b)) {
                    if (strstr(strtolower($b), 'is null') !== false) {
                        $where .= $b;

                    } else {
                        $where .= $b . ' ? AND ';

                    }

                } else {
                    $where .= $b . ' = ? AND ';

                }

            }

        }

        if ($where !== '') {
            return $where;
        }

        return false;

    }

    /**
     * Generate WHERE LIKE statement
     *
     * @param $column
     * @param bool $values
     * @return $this
     */
    public function whereLike($column, $values = false)
    {
        $values = '%' . $values . '%';

        $this->arguments = array_merge(array($values), $this->arguments);
        $this->whereLike = $column . ' LIKE ? ';

        return $this;

    }

    /**
     * Generate WHERE IN statement
     *
     * @param $column
     * @param bool $values
     * @return $this
     */
    public function whereIn($column, $values = false)
    {

        $this->arguments = array_merge(array_values($values), $this->arguments);
        $this->where_in = $column . ' IN ( ' . rtrim(str_repeat('?, ', count($values)), ', ') . ' ) ';

        return $this;

    }

    /**
     * Genere WHERE NOT IN statement
     *
     * @param $column
     * @param bool $values
     * @return $this
     */
    public function whereNotIn($column, $values = false)
    {

        $this->arguments = array_merge(array_values($values), $this->arguments);
        $this->whereNotIn = $column . ' NOT IN ( ' . rtrim(str_repeat('?, ', count($values)), ', ') . ' ) ';

        return $this;

    }

    /**
     * Generate OR WHERE statement
     *
     * @param $field
     * @param bool $value
     * @return $this
     * @throws \Exception
     */
    public function orWhere($field, $value = false)
    {


        $orwhere = $this->whereParamToArray($field, $value);
        $this->orWhereKeyValues = array_merge($orwhere, $this->orWhereKeyValues);
        $this->arguments = array_merge($this->arguments, array_values($orwhere));
        $this->orWhere = $this->prepareWhereValues($this->orWhereKeyValues);

        return $this;

    }

    /**
     * Generate WHERE statement
     *
     * @param $field
     * @param bool $value
     * @param bool $typedConjunction
     * @return $this
     * @throws \Exception
     */
    public function where($field, $value = false, $typedConjunction = false)
    {

        $where = $this->whereParamToArray($field, $value);

        if (is_string($field) && is_array($value)) {
            if ($typedConjunction) {
                $typedConjunction = ' ' . $typedConjunction . ' ';
            }
            $this->typedWhere = $typedConjunction . $field; // why are my typedWhere's showing up ?

        } else {
            $this->whereKeyValues = array_merge($this->whereKeyValues, $where);

            $this->where = $this->prepareWhereValues($this->whereKeyValues);
        }

        if (($key = array_search(false, $where, true)) !== false) {
            unset($where[$key]);
        }
//        print_r($where);
        $this->arguments = array_merge($this->arguments, array_values($where));

        return $this;

    }

    /**
     * Specify table for query
     *
     * @param bool $table
     * @return $this
     * @throws \Exception
     */
    public function table($table = false)
    {

        if (!$table) {
            throw new \Exception('Please specify a table for selection');
        }

        $this->table = $table;

        return $this;

    }

    /**
     * Generate JOIN statement
     *
     * @param $table
     * @param $condition
     * @param string $type
     * @return $this
     */
    public function join($table, $condition, $type = 'LEFT')
    {

        $this->join .= ' ' . $type . ' JOIN ' . $table . ' ON ' . $condition;

        return $this;

    }

    /**
     * Generate SELECT statement
     *
     * @param bool $columns
     * @return $this
     */
    public function select($columns = false)
    {

        $this->columns = (!$columns ? '*' : $columns);

        return $this;

    }

    /**
     * Generate ORDER BY statement
     *
     * @param $sort
     * @param bool $order
     * @return $this
     */
    public function orderBy($sort, $order = false)
    {

        $this->orderBy = ' ORDER BY';

        if (is_array($sort)) {
            foreach ($sort as $column => $order) {
                $this->orderBy .= ' ' . $column . ' ' . $order . ',';

            }

            $this->orderBy = rtrim($this->orderBy, ',');

        } else {
            $this->orderBy .= ' ' . $sort . ' ' . $order;

        }

        return $this;

    }

    /**
     * Generate WHERE BETWEEN statement
     *
     * @param $field
     * @param $start
     * @param $finish
     * @param bool $escape
     */
    public function whereBetween($field, $start, $finish, $escape = false)
    {

        if ($escape) {
            $this->whereBetween = $field . ' BETWEEN "' . $start . '" AND "' . $finish . '"';

        } else {
            $this->whereBetween = $field . ' BETWEEN ' . $start . ' AND ' . $finish;

        }

    }

    /**
     * Generate LIMIT statement
     *
     * @param int $limit
     * @param bool $offset
     * @return $this
     */
    public function limit($limit = 10, $offset = false)
    {

        $this->limit = '';


        $this->limit = ' LIMIT ' . (int) $limit;

        if ($offset) {
            $this->limit .= ' OFFSET ' . (int) $offset;
        }


        return $this;

    }

    /**
     * Fetch the last inserted ID
     *
     * @return mixed
     */
    public function lastInsertId()
    {

        return $this->dbh->lastInsertId();

    }
    /**
     * Perform query directly (prepared statements supported
     * with ? and ordered params)
     *
     * @param $sqlQuery
     * @param array $params
     * @throws \Exception
     */
    //typed queries?
    public function query($sqlQuery, $params = [])
    {

        try {
            $sth = $this->dbh->prepare($sqlQuery);
            $sth->execute($params);

            if($sth->columnCount() === 0) {
                $result = $sth->rowCount();
            } else {
                $result = $sth->rowCount() > 1 ? $sth->fetchAll(\PDO::FETCH_OBJ) : $sth->fetch(\PDO::FETCH_OBJ);
            }

            $this->close($sth);

            return $result;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute a prepared read query
     *
     * @param bool $resultSet
     * @throws \Exception
     */
    public function fetch($resultSet = false)
    {

        $result = false;

        try {
            $prepWhere = $this->prepareWhere();
//            echo 'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $prepWhere  . $this->orderBy . $this->groupBy . $this->limit;
            $sth = $this->dbh->prepare(
                'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $prepWhere . $this->groupBy . $this->orderBy . $this->limit
            );
//

            $sth->execute($this->arguments);


            if ($sth->rowCount() > 0) {
                $result = ($sth->rowCount() > 1 || $resultSet ? $sth->fetchAll(\PDO::FETCH_OBJ) : $sth->fetch(\PDO::FETCH_OBJ));
//                print_r($result);
            }

            $this->close($sth);

            return $result;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute the prepared update query
     *
     * @param $values
     * @throws \Exception
     */
    public function update($values)
    {

        try {
            $fields = array_keys($values);
            $updateColumns = implode(' = ?, ', $fields) . ' = ? ';

            $prepWhere = $this->prepareWhere();

            $this->arguments = array_merge(array_values($values), $this->arguments);

            $sth = $this->dbh->prepare('UPDATE ' . $this->table . $this->join . ' SET ' . $updateColumns . $prepWhere);
//            print_r('UPDATE ' . $this->table . $this->join . ' SET ' . $updateColumns . $prepWhere);
//            print_r($this->arguments);
            $result = $sth->execute($this->arguments);

            $this->close($sth);

            return $result;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute the prepared delete query
     *
     * @throws \Exception
     */
    public function delete()
    {
        try {
            $prepWhere = $this->prepareWhere();
            $sth = $this->dbh->prepare('DELETE FROM ' . $this->table . $prepWhere);

            $result = $sth->execute($this->arguments);

            $this->close($sth);

            return $result;


        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute the prepared insert query
     *
     * @param $values
     * @param bool $command
     * @throws \Exception
     */
    public function insert($values, $command = false)
    {

        try {
            $fields = array_keys($values);
            $val = array();

            foreach ($values as $index => $value) {
                $val[':' . $index] = $value;
            }

            $sth = $this->dbh->prepare(
                'INSERT INTO ' . $this->table . ' (' . implode(', ', $fields) . ') VALUES (:' . implode(
                    ', :',
                    $fields
                ) . ')' . $command
            );
            $result = $sth->execute($val);

            $this->close($sth);

            return $result;


        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute the prepared batch insert
     *
     * @param $batchData
     * @param bool $command
     * @throws \Exception
     */
    public function insertBatch($batchData, $command = false)
    {

        try {
            $fields = array_keys($batchData[0]); //better make sure the insert batch has all the same columns..

            $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $fields) . ') VALUES ';

            $preparedColumns = array();

            foreach ($batchData as $index => $row) {
                $sql .= '(';

                foreach ($row as $column => $value) {
                    $sql .= ':' . $column . $index . ', ';
                    $preparedColumns[$column . $index] = $value;


                }

                $sql = rtrim($sql, ', ');
                $sql .= ')';

                end($batchData);
                if ($index !== key($batchData)) {
                    $sql .= ', ';
                }

            }


//            echo "<PRE>";
//            echo $sql;
//            print_r($preparedColumns);
//            echo "</PRE>";
            $sth = $this->dbh->prepare($sql);


            $result = $sth->execute($preparedColumns);

            $this->close($sth);

            return $result;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Execute the prepared batch update
     *
     * @param $batchData
     * @throws \Exception
     */
    public function updateBatch($batchData)
    {
        //@TODO: convert function to use placeholders ?

        try {
            $index = $batchData['_index'];
            array_shift($batchData);

            $values = array();
            $whereIn = array();

            $sql = 'UPDATE ' . $this->table . ' SET ';

            foreach ($batchData as $column => $updates) {
                $sql .= $column . ' = CASE ' . $index;

                foreach ($updates['values'] as $update) {
                    $whereIn[] = $update['where'];
                    $values[] = $update['change'];
                    $sql .= ' WHEN "' . $update['where'] . '" THEN ? ';

                }

                $sql .= 'ELSE ' . $column . ' END,';

            }

            $sql = rtrim($sql, ',');

            $sql .= ' WHERE ' . $index . ' IN ("' . implode('", "', $whereIn) . '")';

            $sth = $this->dbh->prepare($sql);

            $result = $sth->execute($values); // bind ?

            $this->close($sth);

            return $result;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     * @throws \Exception when database hasn't been invoked.
     */

    public static function &getInstance()
    {

        try {

            if (self::$instance === null) {
                throw new \Exception('Unable to get an instance of the database class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            throw new \Exception($e->getMessage());

        }

    }
}