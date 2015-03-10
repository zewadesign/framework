<?php
// This whole class is pretty n' doesn't lend well to the reader
// but I imagine it's under the hammer.
//TODO add USE Schema query
namespace core;

use \PDO as PDO;

class Database
{
    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */
    protected static $instance;
    private $dbh;
    private $dbhStore = array();
    private $join = '';
    private $groupBy = false;
    private $where;
    private $orWhere = false;
    private $typedWhere = false;
    private $whereBetween = false;
    private $where_in = false;
    private $whereLike = false;
    private $whereNotIn = false;
    private $table;
    private $columns = '*';
    private $orderBy;
    private $limit;
    private $arguments = array();
    private $whereKeyValues = array();
    private $orWhereKeyValues = array();

//    public $tokenizedQuery = array();

    public function __construct($name, $dbConfig)
    {

        if (!empty($dbConfig['dsn']) && !empty($dbConfig['user']) && !empty($dbConfig['pass'])) {
            try {
                $this->dbh = new PDO($dbConfig['dsn'], $dbConfig['user'], $dbConfig['pass']);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->dbhStore[$name] = $this->dbh;

            } catch (PDOException $e) {
                echo $e->getMessage();
            }

        } else {
            throw new \Exception('No can do Jack, you need to provide valid connection details.');

        }

    }

    public function setDSN($name)
    {

        try {
            if (!empty($this->dbhStore[$name])) {
                $this->dbh = $this->dbhStore[$name];

            } else {
                if (empty(Registry::get('_loader')->config('core', 'database')[$name])) {
                    // Throw an exception here
                    throw new \Exception('Please specify connection parameters for: ' . $name . ' in the configuration file.');

                }

                $dbConfig = Registry::get('_loader')->config('core', 'database')[$name];

                $this->dbh = new PDO($dbConfig['dsn'], $dbConfig['user'], $dbConfig['pass']);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $this->dbhStore[$name] = $this->dbh;

            }

        } catch (PDOException $e) {
            // Echo a different exception here.
            echo $e->getMessage();

        }
    }

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

    public function groupBy($column = false)
    {

        if (!$column) {
            throw new \Exception('Please specify a column for the group by.');
        }

//        $this->arguments = array_merge($this->arguments, array($column));
        $this->groupBy = ' GROUP BY ' . $column;

        return $this;

    }

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

    private function hasOperator($str)
    {

        return (bool) preg_match(
            '/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim($str)
        );

    }

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

    //@TODO: add extension for tools that extract plaintext queries into prepared statements & binding
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

    public function whereLike($column, $values = false)
    {
        $values = '%' . $values . '%';

        $this->arguments = array_merge(array($values), $this->arguments);
        $this->whereLike = $column . ' LIKE ? ';

        return $this;

    }

    public function whereIn($column, $values = false)
    {

        $this->arguments = array_merge(array_values($values), $this->arguments);
        $this->where_in = $column . ' IN ( ' . rtrim(str_repeat('?, ', count($values)), ', ') . ' ) ';

        return $this;

    }

    public function whereNotIn($column, $values = false)
    {

        $this->arguments = array_merge(array_values($values), $this->arguments);
        $this->whereNotIn = $column . ' NOT IN ( ' . rtrim(str_repeat('?, ', count($values)), ', ') . ' ) ';

        return $this;

    }

    public function orWhere($field, $value = false)
    {


        $orwhere = $this->whereParamToArray($field, $value);
        $this->orWhereKeyValues = array_merge($orwhere, $this->orWhereKeyValues);
        $this->arguments = array_merge($this->arguments, array_values($orwhere));
        $this->orWhere = $this->prepareWhereValues($this->orWhereKeyValues);

        return $this;

    }

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

    public function table($table = false)
    {

        if (!$table) {
            throw new \Exception('Please specify a table for selection');
        }

        $this->table = $table;

        return $this;

    }

    public function join($table, $condition, $type = 'LEFT')
    {

        $this->join .= ' ' . $type . ' JOIN ' . $table . ' ON ' . $condition;

        return $this;

    }

    public function select($columns = false)
    {

        $this->columns = (!$columns ? '*' : $columns);

        return $this;

    }


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

    public function whereBetween($field, $start, $finish, $escape = false)
    {

        if ($escape) {
            $this->whereBetween = $field . ' BETWEEN "' . $start . '" AND "' . $finish . '"';

        } else {
            $this->whereBetween = $field . ' BETWEEN ' . $start . ' AND ' . $finish;

        }

    }

    public function limit($limit = 10, $offset = false)
    {

        $this->limit = '';


        $this->limit = ' LIMIT ' . (int) $limit;

        if ($offset) {
            $this->limit .= ' OFFSET ' . (int) $offset;
        }


        return $this;

    }


    public function lastInsertId()
    {

        return $this->dbh->lastInsertId();

    }

    //typed queries?
    //dropped result manipulation.. you are querying your own stuff..
    public function query($sqlQuery, $params = array())
    {
//        print_r($params);
        $this->arguments = $params;

        try {
            $sth = $this->dbh->prepare($sqlQuery);

            if (
                strpos(mb_substr(trim($sqlQuery), 0, 6), 'SELECT') !== false ||
                strpos(mb_substr(trim($sqlQuery), 0, 6), 'CALL') !== false
            ) {
                $sth->execute($this->arguments);

                if ($sth->rowCount() > 0) {
                    $result = $sth->rowCount() > 1 ? $sth->fetchAll(PDO::FETCH_OBJ) : $sth->fetch(PDO::FETCH_OBJ);

                }

            } else {
                $result = $sth->execute($this->arguments);

            }

            $this->close($sth);

            return $result;

        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }


    public function fetch($resultSet = false)
    {

        $result = false;

        try {
            $prepWhere = $this->prepareWhere();
//            echo 'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $prepWhere  . $this->orderBy . $this->groupBy . $this->limit;
            $sth = $this->dbh->prepare(
                'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $prepWhere . $this->groupBy . $this->orderBy . $this->limit
            );

//                echo "<PRE>";
//                echo 'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $prepWhere . $this->groupBy . $this->orderBy . $this->limit;
//                print_r($this->arguments);
//                echo "</PRE>";


            $sth->execute($this->arguments);


if ($sth->rowCount() > 0) {
    $result = ($sth->rowCount() > 1 || $resultSet ? $sth->fetchAll(PDO::FETCH_OBJ) : $sth->fetch(PDO::FETCH_OBJ));
//                print_r($result);
}

            $this->close($sth);

            return $result;

        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }


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

        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }

//@TODO: insert transactions / roll backs

    public function delete()
    {


        try {
            $prepWhere = $this->prepareWhere();

            $sth = $this->dbh->prepare('DELETE FROM ' . $this->table . $prepWhere);

            $result = $sth->execute($this->arguments);

            $this->close($sth);

            return $result;


        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }
    //@TODO: convert syntax to ? placeholders ??
    //@TODO: put everything in transactional SQL commands
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


        } catch (PDOException $e) {
            // How come you use throw exceptions in the rest of your app but here you just dump the text of the exception.?.
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }

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
            echo '<pre>', $e->getMessage(), '</pre>';

        }

    }

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

        } catch (PDOException $e) {
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
                throw new Exception('Unable to get an instance of the database class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }
}
