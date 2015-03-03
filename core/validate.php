<?php
// Some kind of form validation library in the core of the framework ..  but doesn't get used?
// This definitely seems out of place at the moment.
namespace core;

use \Exception as Exception;

/**
 * Validation to compare data sets against rules
 *
 * <code>
 *
 * $data = ['fieldname' => 'value', 'fieldname2' => 'value2'];
 *
 * $array = [
 *     'fieldname' => ['name' => 'Field Name', 'rules' => 'required|commonstring'],
 *     'fieldname2' => ['name' => 'Field Name 2', 'rules' => 'required'],
 * ];
 *
 * $bool = $this->validate->run($data, $array);
 *
 * </code>
 *
 * @author unknown, Zechariah Walden<zech @ zewadesign.com>
 */
class Validate
{

    /**
     * System configuration
     *
     * @var object
     */
    private $_configuration;

    /**
     * Instance of output class. //handles sanitization/formatting of system generated strings
     *
     * @access private
     * @var object
     */

    private $_output;

    /**
     * Collects all errors on validation.
     *
     * @access private
     * @var array
     */

    private $errors = [];


    /**
     * Load up some basic configuration settings.
     */

    public function __construct()
    {

        $this->_configuration = Registry::get('_configuration');
        $this->_output = Registry::get('_output');

    }

    /**
     * Validate data against validators.
     *
     * @access public
     *
     * @param array $data The data to be validated
     * @param array $validators The validators
     *
     * @return boolean
     */

    public function run(array $data, array $validators)
    {

        if ($this->_processValidation($data, $validators) === false) {
            return false;
        }

        return true;

    }

    /**
     * Process the errors and return proper language for error
     *
     * @param bool $clear false persist errors
     *
     * @return mixed bool(false) for no errors, array for errors
     */

    public function errors($clear = true)
    {

        if (empty($this->errors)) {
            return false;
        }

        $result = [];

        foreach ($this->errors as $error) {
            $fieldName = $error['fieldName'];

            $result[$fieldName] = [];

            //Convert camelcase to underscore & remove leading underscore.
            $rule = preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", str_replace('_', '', $error['rule']));
            $rule = strtoupper($rule);

            $replace = $error['replace'];//[$field, $params];

            $result[$fieldName][] = $this->_output->lang($rule, $replace);

        }

        if ($clear) {
            $this->errors = [];
        }

        return $result;
    }


    /**
     * Perform data validation against the provided ruleset
     *
     * @access private
     *
     * @param  array $input
     * @param  array $validators
     *
     * @return bool
     * @throws Exception When validation methods do not exist.
     */

    private function _processValidation(array $input, array $validators)
    {

        $validation = true;

        foreach ($validators as $fieldName => $config) {
            $field = $config['name'];

            $ruleset = explode('|', $config['rules']);

            foreach ($ruleset as $rule) {
                $param = false;
                $method = false;

                if (strstr($rule, ',') !== false) {
// has params
                    $split = explode(',', $rule);
                    $method = '_validate' . $split[0];
                    $params = $split[1];
                } else {
                    $method = '_validate' . $rule;
                }

                if (is_callable(array($this, $method))) {
                    $result = $this->$method($field, $fieldName, $input, $param);

                    if ($result) {
// Validation Failed
                        $validation = false;
                        $this->errors[] = $result;
                    }

                } else {
                    throw new Exception("Validate method '$method' does not exist.");
                }
            }
        }

        return $validation;
    }

    /**
     * Check if the specified value is present in database
     *
     * Usage: 'rules' => 'isunique,table.column'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     * @throws Exception If a database connection isn't present.
     */

    private function _validateIsUnique($field, $fieldName, $input, $param = false)
    {

        if (empty($input[$fieldName])) {
            return true;
        }

        if (!$this->_configuration->database) {
            throw new Exception("The is unique validation rule required a valid database connection.");
        }

        $database = Registry::get('_database');

        list($table, $column) = explode('.', $param);

        $unique = $database->select($column)
                           ->table($table)// table
                           ->where($column, $input[$fieldName])
                           ->fetch();

        if (!$unique) {
            return true;
        }

        return ['fieldName' => $fieldName, 'replace' => [$input[$fieldName]]];

    }

    /**
     * Check if the specified key is present and not empty
     *
     * Usage: 'rules' => 'required'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateRequired($field, $fieldName, $input, $param = false)
    {


        if (isset($input[$fieldName]) && !empty($input[$fieldName])) {
            return true;
        }

        return ['fieldName' => $fieldName, 'replace' => [$field]];

    }

    /**
     * Determine if the provided email is valid
     *
     * Usage: 'rules' => 'validemail'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateValidEmail($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field]) || empty($input[$field])) {
            return true;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];

        }
    }

    /**
     * Determine if the provided value length is less or equal to a specific value
     *
     * Usage: 'rules' => 'maxlen,240'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateMatches($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field])) {
            return true;
        }
        if ($input[$field] == $input[$param]) {
            return true;
        }

        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }

    /**
     * Determine if the provided value length is less or equal to a specific value
     *
     * Usage: 'rules' => 'maxlen,240'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateMaxLen($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field])) {
            return true;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) <= (int) $param) {
                return true;
            }
        } else {
            if (strlen($input[$field]) <= (int) $param) {
                return true;
            }
        }

        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }

    /**
     * Determine if the provided value length is more or equal to a specific value
     *
     * Usage: 'rules' => 'minlen,4'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateMinLen($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field])) {
            return true;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) >= (int) $param) {
                return true;
            }
        } else {
            if (strlen($input[$field]) >= (int) $param) {
                return true;
            }
        }
        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }

    /**
     * Determine if the provided value length matches a specific value
     *
     * Usage: 'rules' => 'exactlen,5'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateExactLen($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field])) {
            return true;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) == (int) $param) {
                return true;
            }
        } else {
            if (strlen($input[$field]) == (int) $param) {
                return true;
            }
        }

        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }

    /**
     * Determine if the provided value contains only alpha characters
     *
     * Usage: 'rules' => 'alpha'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateAlpha($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$field]) || empty($input[$field])) {
            return true;
        }

        if (!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i", $input[$field]) !== false
        ) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters
     *
     * Usage: 'rules' => 'commonstring'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateCommonString($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!preg_match(
            "/^([\sa-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ?!.+-])+$/i",
            $input[$fieldName]
        ) !== false
        ) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters
     *
     * Usage: 'rules' => 'alphanumeric'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateAlphaNumeric($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!preg_match(
            "/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i",
            $input[$fieldName]
        ) !== false
        ) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value contains only alpha characters with dashed and underscores
     *
     * Usage: 'rules' => 'alphadash'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateAlphaDash($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!preg_match(
            "/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i",
            $input[$fieldName]
        ) !== false
        ) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid number or numeric string
     *
     * Usage: 'rules' => 'numeric'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateNumeric($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!is_numeric($input[$fieldName])) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid integer
     *
     * Usage: 'rules' => 'integer'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateInteger($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_INT)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a PHP accepted boolean
     *
     * Usage: 'rules' => 'boolean'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateBoolean($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        $bool = filter_var($input[$fieldName], FILTER_VALIDATE_BOOLEAN);

        if (!is_bool($bool)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid float
     *
     * Usage: 'rules' => 'float'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateFloat($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_FLOAT)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid URL
     *
     * Usage: 'rules' => 'validurl'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */
    private function _validateValidURL($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_URL)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if a URL exists & is accessible
     *
     * Usage: 'rules' => 'urlexists'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateURLExists($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        $url = str_replace(
            array('http://', 'https://', 'ftp://'),
            '',
            strtolower($input[$fieldName])
        );

        if (function_exists('checkdnsrr')) {
            if (!checkdnsrr($url)) {
                return ['fieldName' => $fieldName, 'replace' => [$field]];
            }
        } else {
            if (gethostbyname($url) == $url) {
                return ['fieldName' => $fieldName, 'replace' => [$field]];
            }
        }
    }

    /**
     * Determine if the provided value is a valid IP address
     *
     * Usage: 'rules' => 'validip'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateValidIp($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_IP) !== false) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid IPv4 address
     *
     * Usage: 'rules' => 'validipv4'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     * @see http://pastebin.com/UvUPPYK0
     */

    /*
     * What about private networks? http://en.wikipedia.org/wiki/Private_network
     * What about loop-back address? 127.0.0.1
     */
    private function _validateValidIpv4($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
// removed !== FALSE
         // it passes
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided value is a valid IPv6 address
     *
     * Usage: 'rules' => 'validipv6'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateValidIpv6($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (!filter_var($input[$fieldName], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the input is a valid credit card number
     *
     * See: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
     * Usage: 'rules' => 'validcc'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateValidCc($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        $number = preg_replace('/\D/', '', $input[$fieldName]);

        if (function_exists('mb_strlen')) {
            $number_length = mb_strlen($number);
        } else {
            $number_length = strlen($number);
        }

        $parity = $number_length % 2;

        $total = 0;

        for ($i = 0; $i < $number_length; $i ++) {
            $digit = $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        if ($total % 10 == 0) {
            return; // Valid
        }

        return ['fieldName' => $fieldName, 'replace' => [$field]];
    }

    /**
     * Determine if the provided input is a valid date (ISO 8601)
     *
     * Usage: 'rules' => 'date'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateDate($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        $cdate1 = date('m/d/Y', strtotime($input[$fieldName]));
        $cdate2 = date('m/d/Y H:i:s', strtotime($input[$fieldName]));

        if ($cdate1 != $input[$fieldName] && $cdate2 != $input[$fieldName]) {
            return ['fieldName' => $fieldName, 'replace' => [$field]];
        }
    }

    /**
     * Determine if the provided numeric value is lower or equal to a specific value
     *
     * Usage: 'rules' => 'maxnumeric,50'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateMaxNumeric($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (is_numeric($input[$fieldName]) && is_numeric($param) && ($input[$fieldName] <= $param)) {
            return true;
        }

        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }

    /**
     * Determine if the provided numeric value is higher or equal to a specific value
     *
     * Usage: 'rules' => 'minnumeric,1'
     *
     * @access private
     *
     * @param  string $field friendly display name
     * @param  string $fieldName post field name
     * @param  array $input data
     * @param  mixed $param
     *
     * @return mixed
     */

    private function _validateMinNumeric($field, $fieldName, $input, $param = false)
    {

        if (!isset($input[$fieldName]) || empty($input[$fieldName])) {
            return true;
        }

        if (is_numeric($input[$fieldName]) && is_numeric($param) && ($input[$fieldName] >= $param)) {
            return true;
        }

        return ['fieldName' => $fieldName, 'replace' => [$field, $param]];
    }
}
