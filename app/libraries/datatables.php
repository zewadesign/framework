<?php
namespace app\libraries;
use core\Registry;

/**
 * Ignited Datatables
 *
 * This is a wrapper class/library based on the native Datatables server-side implementation by Allan Jardine
 * found at http://datatables.net/examples/data_sources/server_side.html for CodeIgniter
 *
 * @package    CodeIgniter
 * @subpackage libraries
 * @category   library
 * @version    2.0 <beta>
 * @author     Vincent Bambico <metal.conspiracy@gmail.com>
 *             Yusuf Ozdemir <yusuf@ozdemir.be>
 * @link       http://ellislab.com/forums/viewthread/160896/
 *
 *
 *
 * NOTE
 * REMIXXXXXXXXXXXXXXXXXXXXX!!!!!!! Retrofitted ignited tables to be used as datatable library for my active records
 *
 */
class Datatables
{

    /**
     * Global container variables for chained argument results
     *
     */
    private $database;
    private $request;
    private $table;
    private $group_by = array();
    private $select = array();
    private $joins = array();
    private $columns = array();
    private $where = array();
    private $whereBetween = array();
    private $or_where = array();
    private $like = array();
    private $filter = array();
    private $add_columns = array();
    private $edit_columns = array();
    private $unset_columns = array();
    private $index;

    public function __construct() {

        $this->database = Registry::get('_database');
        $this->request = Registry::get('_request');

    }

    public function index($index) {
        $this->index = $index;

        return $this;
    }

    /**
     * Generates the SELECT portion of the query
     *
     * @param string $columns
     * @param bool $backtick_protect
     * @return mixed
     */
    public function select($columns, $backtick_protect = TRUE) {

        foreach($this->explode(',', $columns) as $val) {
            $column                = trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$2', $val));
            $this->columns[]       = $column;
            $this->select[$column] = trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$1', $val));
        }

        $this->database->select($columns);

        return $this;
    }


    /**
     * Generates a custom GROUP BY portion of the query
     *
     * @param string $val
     * @return mixed
     */
    public function groupBy($val) {

        $this->group_by[] = $val;
        $this->database->groupBy($val);

        return $this;
    }

    /**
     * Generates the FROM portion of the query
     *
     * @param string $table
     * @return mixed
     */
    public function table($table) {

        $this->table = $table;

        return $this;
    }

    /**
     * Generates the JOIN portion of the query
     *
     * @param string $table
     * @param string $fk
     * @param string $type
     * @return mixed
     */
    public function join($table, $fk, $type = false) {

        $this->joins[] = array($table, $fk, $type);

        if($type) {

            $this->database->join($table, $fk, $type);

        } else {

            $this->database->join($table, $fk);

        }

        return $this;
    }

    /**
     * Generates the WHERE portion of the query
     *
     * @param mixed $key_condition
     * @param string $val
     * @param bool $backtick_protect
     * @return mixed
     */
    public function where($key_condition, $val = false) {

        $this->where[] = array($key_condition, $val);

        if($val) {

            $this->database->where($key_condition, $val);

        } else {

            $this->database->where($key_condition);

        }


        return $this;
    }


    public function whereBetween($field, $start, $finish, $escape = false) {
        $this->whereBetween = array($field, $start, $finish, $escape);
        $this->database->whereBetween($field, $start, $finish, $escape);
    }

    /**
     * Generates the WHERE portion of the query
     *
     * @param mixed $key_condition
     * @param string $val
     * @param bool $backtick_protect
     * @return mixed
     */
    public function orWhere($key_condition, $val = false) {

        $this->or_where[] = array($key_condition, $val);

        if($val) {

            $this->database->orWhere($key_condition, $val);

        } else {

            $this->database->orWhere($key_condition);

        }

        return $this;
    }

    /**
     * Generates the WHERE portion of the query
     *
     * @param mixed $key_condition
     * @param string $val
     * @param bool $backtick_protect
     * @return mixed
     */
    public function filter($key_condition, $val = NULL) {

        $this->filter[] = array($key_condition, $val);

        return $this;
    }

    /**
     * Generates a %LIKE% portion of the query
     *
     * @param mixed $key_condition
     * @param string $val
     * @param bool $backtick_protect
     * @return mixed
     */
    public function like($key_condition, $val = false) {

        $this->like[] = array($key_condition, $val);

        if($val) {

            $this->database->like($key_condition, $val);

        } else {

            $this->database->like($key_condition, '%'.$val.'%');

        }


        return $this;
    }

    /**
     * Sets additional column variables for adding custom columns
     *
     * @param string $column
     * @param string $content
     * @param string $match_replacement
     * @return mixed
     */
    public function add_column($column, $content, $match_replacement = NULL) {

        $this->add_columns[$column] = array('content' => $content, 'replacement' => $this->explode(',', $match_replacement));

        return $this;
    }

    /**
     * Sets additional column variables for editing columns
     *
     * @param string $column
     * @param string $content
     * @param string $match_replacement
     * @return mixed
     */
    public function edit_column($column, $match_replacement, $callback) {

        $this->edit_columns[$column][] = array('replacement' => $this->explode(',', $match_replacement), 'callback' => $callback);

        return $this;
    }

    /**
     * Unset column
     *
     * @param string $column
     * @return mixed
     */
    public function unset_column($column) {

        $column              = explode(',', $column);
        $this->unset_columns = array_merge($this->unset_columns, $column);

        return $this;
    }

    /**
     * Builds all the necessary query segments and performs the main query based on results set from chained statements
     *
     * @param string $output
     * @param string $charset
     * @return string
     */
    public function generate($output = 'json', $charset = 'UTF-8') {

        if(strtolower($output) == 'json')
            $this->get_paging();

        $this->get_ordering();
        $this->get_filtering();

        return $this->produce_output(strtolower($output), strtolower($charset));
    }

    /**
     * Generates the LIMIT portion of the query
     *
     * @return mixed
     */
    private function get_paging() {

        $iStart  = $this->request->post('start');
        $iLength = $this->request->post('length');

        if($iLength != '' && $iLength != '-1')
            $this->database->limit((int)$iLength, ($iStart) ? (int)$iStart : 0);
    }

    /**
     * Generates the ORDER BY portion of the query
     *
     * @return mixed
     */
    private function get_ordering() {


        $data = $this->request->post('columns');

        foreach($this->request->post('order') as $key) {

            if($this->check_cType()) {

                $this->database->orderBy($data[$key['column']]['data'], $key['dir']);

            } else {

                $col = array_values(array_diff_key($this->columns, $this->unset_columns));
                $this->database->orderBy($col[$key['column']], $key['dir']);

            }

        }

    }

    /**
     * Generates a %LIKE% portion of the query
     *
     * @return mixed
     */
    private function get_filtering() {

        $mColArray = $this->request->post('columns');

        $sWhere = '';
        $search = $this->request->post('search');
        $sSearch = trim($search['value']);
        $columns = array_values(array_diff($this->columns, $this->unset_columns));

        if($sSearch != '') {
            $preparedValues = array();

            for($i = 0; $i < count($mColArray); $i++) {
                if($mColArray[$i]['searchable'] == 'true') {
                    $preparedValues[] = '%' . $sSearch . '%';
                    if($this->check_cType()) {
                        $sWhere .= $this->select[$mColArray[$i]['data']] . " LIKE ? OR ";
                    } else {
                        $sWhere .= $this->select[$columns[$i]] . " LIKE ? OR ";
                    }
                }
            }

            $sWhere = substr_replace($sWhere, '', -3);

            if($sWhere != '') {
                if(!empty($this->where)) {
                    $this->database->where('(' . $sWhere . ')', $preparedValues, 'AND');
                } else {
                    $this->database->where('(' . $sWhere . ')', $preparedValues);
                }
            } else {
                return FALSE;
            }
        }
    }

    /**
     * Compiles the select statement based on the other functions called and runs the query
     *
     * @return mixed
     */
    private function get_display_result() {

        return json_decode(json_encode($this->database->table($this->table)->fetch('result')), TRUE);
    }

    /**
     * Builds an encoded string data. Returns JSON by default, and an array of aaData if output is set to raw.
     *
     * @param string $output
     * @param string $charset
     * @return mixed
     */
    private function produce_output($output) {

        $aaData  = array();
        $rResult = $this->get_display_result();

        if($output == 'json') {
            $iTotal         = $this->get_total_results();
            $iFilteredTotal = $this->get_total_results(TRUE);
        }

        if(!empty($rResult)) {
            foreach($rResult as $row_key => $row_val) {
                $aaData[$row_key] = ($this->check_cType()) ? $row_val : array_values($row_val);

                foreach($this->add_columns as $field => $val) {
                    if($this->check_cType()) {
                        $aaData[$row_key][$field] = $this->exec_replace($val, $aaData[$row_key]);
                    } else {
                        $aaData[$row_key][] = $this->exec_replace($val, $aaData[$row_key]);
                    }
                }

                foreach($this->edit_columns as $modkey => $modval) {


                    foreach($modval as $index => $val) {
                        $col = ($this->check_cType()) ? $modkey : array_search($modkey, $this->columns);
                        if(is_callable($val['callback'])) {
                            $args = array();

                            foreach($val['replacement'] as $replace) {
                                $args[] = $aaData[$row_key][array_search(trim($replace), $this->columns)];
                            }

                            $aaData[$row_key][$col] = call_user_func_array($val['callback'], $args);

                        }
                    }
                }
                $aaData[$row_key] = array_diff_key($aaData[$row_key], ($this->check_cType()) ? $this->unset_columns : array_intersect($this->columns, $this->unset_columns));
                $aaData[$row_key] = array_diff_key($aaData[$row_key], $this->unset_columns);

                if(!$this->check_cType())
                    $aaData[$row_key] = array_values($aaData[$row_key]);

            }
        }
        if($output == 'json') {
            $sOutput = array
            (
                'draw'            => intval($this->request->post('draw')),
                'recordsTotal'    => $iTotal,
                'recordsFiltered' => $iFilteredTotal,
                'data'            => $aaData
            );

            return json_encode($sOutput);
        } else
            return array('aaData' => $aaData);
    }

    /**
     * Get result count
     *
     * @return integer
     */
    private function get_total_results($filtering = FALSE) {
//        print_r($this->columns);
        $this->database->select($this->index);

        if($filtering)
            $this->get_filtering();

        foreach($this->joins as $val)
            $this->database->join($val[0], $val[1]);

        foreach($this->where as $val)
            $this->database->where($val[0], $val[1]);

        foreach($this->or_where as $val)
            $this->database->orWhere($val[0], $val[1]);

        foreach($this->group_by as $val)
            $this->database->groupBy($val);

        foreach($this->like as $val)
            $this->database->like($val[0], $val[1]);

        if($this->whereBetween)
            $this->database->whereBetween($this->whereBetween[0], $this->whereBetween[1], $this->whereBetween[2], $this->whereBetween[3]);
//        if(strlen($this->distinct) > 0) {
//            $this->database->distinct($this->distinct);
//            $this->database->select($this->columns);
//        }

        $query = $this->database->table($this->table)
            ->fetch('result');

        return count($query);
    }

    /**
     * Runs callback functions and makes replacements
     *
     * @param mixed $custom_val
     * @param mixed $row_data
     * @return string $custom_val['content']
     */
    private function exec_replace($custom_val, $row_data) {

        if(isset($custom_val['replacement']) && is_array($custom_val['replacement']))
        {
            foreach($custom_val['replacement'] as $key => $val)
            {
                $sval = preg_replace("/(?<!\w)([\'\"])(.*)\\1(?!\w)/i", '$2', trim($val));

                if(in_array($sval, $this->columns))
                    $replace_string = $row_data[($this->check_cType())? $sval : array_search($sval, $this->columns)];
                else
                    $replace_string = $sval;

                $custom_val['content'] = str_ireplace('$' . ($key + 1), $replace_string, $custom_val['content']);
            }
        }

        return $custom_val['content'];
    }

    /**
     * Check column type -numeric or column name
     *
     * @return bool
     */
    private function check_cType() {

        $column = $this->request->post('columns');
        if(is_numeric($column[0]['data']))
            return FALSE;
        else
            return TRUE;
    }


    /**
     * Return the difference of open and close characters
     *
     * @param string $str
     * @param string $open
     * @param string $close
     * @return string $retval
     */
    private function balanceChars($str, $open, $close) {

        $openCount  = substr_count($str, $open);
        $closeCount = substr_count($str, $close);
        $retval     = $openCount - $closeCount;

        return $retval;
    }

    /**
     * Explode, but ignore delimiter until closing characters are found
     *
     * @param string $delimiter
     * @param string $str
     * @param string $open
     * @param string $close
     * @return mixed $retval
     */
    private function explode($delimiter, $str, $open = '(', $close = ')') {

        $retval  = array();
        $hold    = array();
        $balance = 0;
        $parts   = explode($delimiter, $str);

        foreach($parts as $part) {
            $hold[] = $part;
            $balance += $this->balanceChars($part, $open, $close);

            if($balance < 1) {
                $retval[] = implode($delimiter, $hold);
                $hold     = array();
                $balance  = 0;
            }
        }

        if(count($hold) > 0)
            $retval[] = implode($delimiter, $hold);

        return $retval;
    }

}