<?php
namespace core;
//blacklist, removepunctuation,sanitizestring,urlencode, htmlencode,sanitizeemail,sanitizenumbers,basictags
/**
//@TODO: when printing out content from database, escape with htmlspecialchars and utf-8 & xxs
//            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');


 * Output - output sanitization
 *
 * @author      Zech
 * @copyright   Copyright (c) 2014 zewadesign.com
 * @version     1.0
 */
class Output
{

    private $load;

    private $basictags = "<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>";

    private $blacklist = "ass,fuck,shit,damn,cunt,whore,bitch,fag,dick,cock";

    /**
     * fetch myself
     *
     */
//@TODO: deconstruct variables at earliest possible time for memory saving.
//@TODO: load evertyhign through the registry for forward facing stuff
    public function __construct() {

        $this->load = Registry::get('_load');
        $this->loadedLanguage = $this->load->lang($this->load->config('core','language'));
    }

    public function lang($language, $replace = false) {

        $language = strtoupper($language);
        $lang = $this->loadedLanguage[$language];

        if($replace) {
            if(is_array($replace)) {
                $lang = vsprintf($this->loadedLanguage[$language], $replace);
            } else {
                $lang = sprintf($this->loadedLanguage[$language], $replace);
            }

        }

        return $lang;

    }
    /**
     * Shorthand method for running only the data filters
     *
     * @param array $data
     * @param array $filters
     */
    public function prepare($data, $filters = array()) {
        return $this->_filter($data, $filters);
    }

    /**
     * Filter the input data according to the specified filter set
     *
     * @access private
     * @param  mixed $input
     * @param  array $filterset
     * @return mixed
     */
    //@TODO: make sure all output is sanitized
    private function _filter($data, array $filterset) {
//        echo "<PRE>";
//        print_r($data);
//        print_r($filterset);
//        $contentIterationCount = count($data);
//        var_dump($data);
        if($data === NULL) return false;

        $string = false;
        if(is_string($data)) {
            $string = true;
//        } else {
//            $data = json_decode (json_encode ($data), FALSE);
        }

//        echo "<PRE>";
//        print_r($data);
//        print_r($filterset);
        foreach($filterset as $field => $filters) {

            if(is_object($data) && !array_key_exists_r($field, $data)) {
                continue;
            }

            $filters = explode('|', $filters);

            foreach($filters as $filter) {
                $params = NULL;

                if(strstr($filter, ',') !== FALSE) {
                    $filter = explode(',', $filter);

                    $params = array_slice($filter, 1, count($filter) - 1);

                    $filter = $filter[0];
                }

                if(is_callable(array($this, '_filter' . $filter))) {
                    $method        = '_filter' . $filter;

                    if($string === false) {
                        foreach ($data as $k => $v) {
                            if(is_object($data) || is_object($data[$k])) {
                                if(isset($data->$field) && !is_object($data->$field)) {

                                    $data->$field = $this->$method($data->$field, $params);

                                } else {
                                    $data[$k]->$field = $this->$method($data[$k]->$field, $params);
                                }

                            } else if(is_array($data)) {

                                if(isset($data[$field]) && !is_array($data[$field])) {

                                    $data[$field] = $this->$method($data[$field], $params);
                                } else { //

                                    $data[$k][$field] = $this->$method($data[$k][$field], $params);
                                }

                            }
                        }
                    } else {
                        $data = $this->$method($data, $params);
                    }

                } else {
                    throw new \Exception("Filter method '$filter' does not exist.");
                }
            }
        }

        return $data;
    }

    // ** ------------------------- Filters --------------------------------------- ** //

    /**
     * Replace tidies html for valid html
     *
     * Usage: '<index>' => 'tidy'
     *
     * @access private
     * @param  string $value
     * @param  array $params
     * @return string
     */
    private function _filterTidy($value, $params = NULL) {

        $tidy = new \tidy;
        $config = array( 'indent' => true, 'output-xhtml' => true, 'wrap' => 200, 'clean' => true, 'show-body-only' => true );
        $tidy->parseString( $value, $config, 'utf8' );
        $tidy->cleanRepair();
        $value = $tidy;

        return $value;

    }


    /**
     * Truncates a string and appends elippsis
     *
     * Usage: '<index>' => 'truncate','param'
     *
     * @access private
     * @param  string $value
     * @param  array $params
     * @return string
     */
    private function _filterTruncate($value, $params = NULL) {
        $append = '&hellip;';
        $break = " ";
        $limit = $params[0];

        if(strlen($value) <= $limit) {
            return $value;
        }

        if(false !== ($breakpoint = strpos($value, $break, $limit))) {
            if($breakpoint < strlen($value) - 1) {
                $value = substr($value, 0, $breakpoint) . $append;
            }
        }
        return $value;
    }


    /**
     * Replace blacklist in a string
     *
     * Usage: '<index>' => 'filterblacklist'
     *
     * @access private
     * @param  string $value
     * @param  array $params
     * @return string
     */
    private function _filterBlacklist($value, $params = NULL) {

        $value = preg_replace('/\s\s+/u', chr(32), $value);

        $value = " $value ";

        $words = explode(',', $this->blacklist);

        foreach($words as $word) {
            $word = trim($word);

            $word = " $word "; // Normalize

            if(stripos($value, $word) !== FALSE) {
                $value = str_ireplace($word, chr(32), $value);
            }
        }

        return trim($value);
    }

    /**
     * Remove all known punctuation from a string
     *
     * Usage: '<index>' => 'rmpunctuataion'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterRemovePunctuation($value) {

        return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
    }


    /**
     * Sanitize the string by removing any script tags
     *
     * Usage: '<index>' => 'sanitizestring'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterSanitizeString($value) {

        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitize the string by urlencoding characters
     *
     * Usage: '<index>' => 'urlencode'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterURLencode($value) {

        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities
     *
     * Usage: '<index>' => 'htmlencode'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterHTMLencode($value) {

        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Sanitize the string by removing illegal characters from emails
     *
     * Usage: '<index>' => 'sanitizeemail'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterSanitizeEmail($value) {

        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize the string by removing illegal characters from numbers
     *
     * Usage: '<index>' => 'sanitizenumbers'
     *
     * @access private
     * @param  string $value
     * @return string
     */
    private function _filterSanitizeNumbers($value) {

        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Filter out all HTML tags except the defined basic tags
     *
     * Usage: '<index>' => 'basictags'
     *
     * @access private
     * @param  string $value
     * @param  array $params
     * @return string
     */
    private function _filterBasicTags($value) {

        return strip_tags($value, $this->basictags);
    }

}