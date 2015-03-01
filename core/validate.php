<?php

namespace core;

/**
 * An instance of this class represents a counting machine
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
     * @var object
     */

    private $_output;

    /**
     * Collects all errors on validation.
     *
     * @var array
     */

    private $errors = array();

    /**
     * Reference to instantiated validation object.
     *
     * @var object
     */

    public static $instance;

    public function __construct() {

        $this->_configuration = Registry::get('_configuration');
        $this->_output = Registry::get('_output');

    }

    /**
     * Validate data against validators.
     *
     * @param array $data The data to be validated
     * @param array $validators The validators
     * @return boolean
     */
    public function run(array $data, array $validators) {

        if($this->_checkValidation($data, $validators) === FALSE) {
            return false;
        }

        return TRUE;

    }

    /**
     * Run the filtering and validation after each other
     *
     * @param array $data
     * @return array
     * @return boolean
     */

    private function _checkValidation(array $data, array $validators) {

        $validated = $this->_processValidation(
            $data, $validators
        );

        if($validated !== TRUE) {
            return FALSE;
        }

        return $data;
    }

    /**
     * Perform data validation against the provided ruleset
     *
     * @access public
     * @param  mixed $input
     * @param  array $ruleset
     * @return mixed
     */
    private function _processValidation(array $input, array $validators) {

        $this->errors = array();

        foreach($validators as $field => $config) {

            $formDisplayName = $config['name'];

            $ruleset = explode('|', $config['rules']);


            foreach($ruleset as $rule) {
                $method = NULL;
                $param  = NULL;

                if(strstr($rule, ',') !== FALSE) // has params
                {
                    $rule   = explode(',', $rule);
                    $method = '_validate' . $rule[0];
                    $param  = $rule[1];
                    $rule   = $rule[0];
                } else {
                    $method = '_validate' . $rule;
                }

                if(is_callable(array($this, $method))) {

                    $result = $this->$method($formDisplayName, $field, $input, $param);

                    if(is_array($result)) // Validation Failed
                    {
                        $this->errors[] = $result;
                    }

                } else {
                    throw new \Exception("Validator method '$method' does not exist.");
                }
            }
        }

        return (count($this->errors) > 0) ? $this->errors : TRUE;
    }

    /**
     * Process the errors and return proper language for error
     *
     * @param bool $convert_to_string = false
     * @param string $field_class
     * @param string $error_class
     * @return array
     * @return string
     */
    public function errors() {

        $formattedErrors = array();

        foreach($this->errors as $error) {
            $field = $error['field'];
            $stringReplacement = $error['friendlyName'];
            $param = $error['param'];

            $formattedErrors[$field] = array();

            switch($error['rule']) {
                case '_validateRequired':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_REQUIRED',$stringReplacement);
                    break;
                case '_validateValidEmail':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_VALID_EMAIL', $stringReplacement);
                    break;
                case '_validateMaxLen':
                    if($param == 1) {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MAX_LEN', array($stringReplacement, $param));
                    } else {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_mAX_LEN2', array($stringReplacement, $param));
                    }
                    break;
                case '_validateMinLen':
                    if($param == 1) {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MIN_LEN', array($stringReplacement, $param));
                    } else {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MIN_LEN2', array($stringReplacement, $param));
                    }
                    break;
                case '_validateExactLen':
                    if($param == 1) {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_EXACT_LEN', array($stringReplacement, $param));
                    } else {
                        $formattedErrors[$field][] = $this->_output->lang('VALIDATE_EXACT_LEN2', array($stringReplacement, $param));
                    }
                    break;
                case '_validateAlpha':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_ALPHA', $stringReplacement);
                    break;
                case '_validateAlphaNumeric':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_ALPHA_NUMERIC', $stringReplacement);
                    break;
                case '_validateAlphaDash':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_ALPHA_DASH', $stringReplacement);
                    break;
                case '_validateNumeric':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_NUMERIC', $stringReplacement);
                    break;
                case '_validateInteger':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_INTEGER', $stringReplacement);
                    break;
                case '_validateBoolean':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_BOOLEAN', $stringReplacement);
                    break;
                case '_validateFloat':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_FLOAT', $stringReplacement);
                    break;
                case '_validateValidURL':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_VALID_URL', $stringReplacement);
                    break;
                case '_validateURLExists':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_URL_EXISTS', $stringReplacement);
                    break;
                case '_validateValidIp':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_VALID_IP', $stringReplacement);
                    break;
                case '_validateValidCc':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_VALID_CC', $stringReplacement);
                    break;
                case '_validateDate':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_VALID_DATE', $stringReplacement);
                    break;
                case '_validateMinNumeric':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MIN_NUMERIC', array($stringReplacement, $param));
                    break;
                case '_validateMaxNumeric':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MAX_NUMERIC', array($stringReplacement, $param));
                    break;
                case '_validateIsUnique':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_IS_UNIQUE', $error['value']);
                    break;
                case '_validateMatches':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_MATCH', $stringReplacement);
                    break;
                case '_validateCommonString':
                    $formattedErrors[$field][] = $this->_output->lang('VALIDATE_COMMON_STRING',$stringReplacement);
            }
        }

        return $formattedErrors;
    }


    // ** ------------------------- Validators ------------------------------------ ** //


    /**
     * Check if the specified key is present and not empty
     *
     * Usage: '<index>' => 'required'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateIsUnique($fieldName, $field, $input, $param = NULL) {

        if(empty($input[$field])) return;



        $database = Registry::get('_database');

        $sqlFragments = explode('.',$param);

        $unique = $database->select($sqlFragments[1])
            ->table($sqlFragments[0]) // table
            ->where($sqlFragments[1], $input[$field])
            ->fetch();

        if(!$unique) {
            return;
        }

        return array(
            'name' => $fieldName,
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param
        );
    }
    /**
     * Check if the specified key is present and not empty
     *
     * Usage: '<index>' => 'required'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateRequired($displayName, $field, $input, $param = NULL) {


        if(isset($input[$field]) && !empty($input[$field])) {
            return;
        }

        return array(
            'field' => $field,
            'value' => NULL,
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided email is valid
     *
     * Usage: '<index>' => 'valid_email'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateValidEmail($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value length is less or equal to a specific value
     *
     * Usage: '<index>' => 'max_len,240'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */

    //@TODO put all validation into filter_vars with a callback on special needs

    private function _validateMatches($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field])) {
            return;
        }
        if($input[$field] == $input[$param]) {

            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided value length is less or equal to a specific value
     *
     * Usage: '<index>' => 'max_len,240'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */

    //@TODO put all validation into filter_vars with a callback on special needs

    private function _validateMaxLen($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field])) {
            return;
        }

        if(function_exists('mb_strlen')) {
            if(mb_strlen($input[$field]) <= (int)$param) {
                return;
            }
        } else {
            if(strlen($input[$field]) <= (int)$param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided value length is more or equal to a specific value
     *
     * Usage: '<index>' => 'min_len,4'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateMinLen($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field])) {
            return;
        }

        if(function_exists('mb_strlen')) {
            if(mb_strlen($input[$field]) >= (int)$param) {
                return;
            }
        } else {
            if(strlen($input[$field]) >= (int)$param) {
                return;
            }
        }
        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided value length matches a specific value
     *
     * Usage: '<index>' => 'exact_len,5'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateExactLen($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field])) {
            return;
        }

        if(function_exists('mb_strlen')) {
            if(mb_strlen($input[$field]) == (int)$param) {
                return;
            }
        } else {
            if(strlen($input[$field]) == (int)$param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided value contains only alpha characters
     *
     * Usage: '<index>' => 'alpha'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateAlpha($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i", $input[$field]) !== FALSE) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters
     *
     * Usage: '<index>' => 'alpha_numeric'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateCommonString($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!preg_match("/^([\sa-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ?!.+-])+$/i", $input[$field]) !== FALSE) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters
     *
     * Usage: '<index>' => 'alpha_numeric'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateAlphaNumeric($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i", $input[$field]) !== FALSE) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha characters with dashed and underscores
     *
     * Usage: '<index>' => 'alpha_dash'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateAlphaDash($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i", $input[$field]) !== FALSE) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid number or numeric string
     *
     * Usage: '<index>' => 'numeric'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateNumeric($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!is_numeric($input[$field])) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid integer
     *
     * Usage: '<index>' => 'integer'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateInteger($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_INT)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a PHP accepted boolean
     *
     * Usage: '<index>' => 'boolean'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateBoolean($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $bool = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN);

        if(!is_bool($bool)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid float
     *
     * Usage: '<index>' => 'float'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateFloat($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_FLOAT)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid URL
     *
     * Usage: '<index>' => 'valid_url'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateValidURL($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_URL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if a URL exists & is accessible
     *
     * Usage: '<index>' => 'url_exists'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateURLExists($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $url = str_replace(
            array('http://', 'https://', 'ftp://'), '', strtolower($input[$field])
        );

        if(function_exists('checkdnsrr')) {
            if(!checkdnsrr($url)) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                    'formDisplayName' => $displayName
                );
            }
        } else {
            if(gethostbyname($url) == $url) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                    'formDisplayName' => $displayName
                );
            }
        }
    }

    /**
     * Determine if the provided value is a valid IP address
     *
     * Usage: '<index>' => 'valid_ip'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateValidIp($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_IP) !== FALSE) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid IPv4 address
     *
     * Usage: '<index>' => 'valid_ipv4'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     * @see http://pastebin.com/UvUPPYK0
     */

    /*
     * What about private networks? http://en.wikipedia.org/wiki/Private_network
     * What about loop-back address? 127.0.0.1
     */
    private function _validateValidIpv4($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) // removed !== FALSE
        { // it passes
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided value is a valid IPv6 address
     *
     * Usage: '<index>' => 'valid_ipv6'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateValidIpv6($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(!filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the input is a valid credit card number
     *
     * See: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
     * Usage: '<index>' => 'valid_cc'
     *
     * @access private
     * @param  string $field
     * @param  array $input
     * @return mixed
     */
    private function _validateValidCc($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $number = preg_replace('/\D/', '', $input[$field]);

        if(function_exists('mb_strlen')) {
            $number_length = mb_strlen($number);
        } else {
            $number_length = strlen($number);
        }

        $parity = $number_length % 2;

        $total = 0;

        for($i = 0; $i < $number_length; $i++) {
            $digit = $number[$i];

            if($i % 2 == $parity) {
                $digit *= 2;

                if($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        if($total % 10 == 0) {
            return; // Valid
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided input is a valid date (ISO 8601)
     *
     * Usage: '<index>' => 'date'
     *
     * @access private
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param null $param
     *
     * @return mixed
     */
    private function _validateDate($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $cdate1 = date('m/d/Y', strtotime($input[$field]));
        $cdate2 = date('m/d/Y H:i:s', strtotime($input[$field]));

        if($cdate1 != $input[$field] && $cdate2 != $input[$field]) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
                'formDisplayName' => $displayName
            );
        }
    }

    /**
     * Determine if the provided numeric value is lower or equal to a specific value
     *
     * Usage: '<index>' => 'max_numeric,50'
     *
     * @access private
     *
     * @param  string $field
     * @param  array $input
     * @param null $param
     *
     * @return mixed
     */
    private function _validateMaxNumeric($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(is_numeric($input[$field]) && is_numeric($param) && ($input[$field] <= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }

    /**
     * Determine if the provided numeric value is higher or equal to a specific value
     *
     * Usage: '<index>' => 'min_numeric,1'
     *
     * @access private
     *
     * @param  string $field
     * @param  array $input
     * @param null $param
     *
     * @return mixed
     */
    private function _validateMinNumeric($displayName, $field, $input, $param = NULL) {

        if(!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if(is_numeric($input[$field]) && is_numeric($param) && ($input[$field] >= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule'  => __FUNCTION__,
            'param' => $param,
            'formDisplayName' => $displayName
        );
    }
} // EOC

