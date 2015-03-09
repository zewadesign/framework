<?php
namespace core;

use \Exception as Exception;

/**
 * Output santization
 *
 * <code>
 *
 * $data = $this->output->prepare($data, ['index' => 'truncate,150', 'title' => 'truncate,35'));
 *
 * </code>
 *
 * @author unknown, Zechariah Walden<zech @ zewadesign.com>
 *
 */
class Output
{

    /**
     * Instantiated load class pointer
     *
     * @var object
     */

    private $load;

    /**
     * Holds the loaded system language
     *
     * @var array
     */
    private $language;

    /**
     * Blacklisted html tags
     *
     * @var string
     */

    private $basictags = "<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>";

    /**
     * Comma delimited list of blacklisted words
     *
     * @var string
     */

    private $blacklist = "ass,fuck,shit,damn,cunt,whore,bitch,fag,dick,cock";

    /**
     * Load up some basic configuration settings.
     */

    public function __construct($load)
    {
        $this->load = $load;
        $this->language = $this->load->lang($this->load->config('core', 'language'));
    }

    /**
     * Replace with system language
     *
     * @param string $selection
     * @param mixed $replace
     *
     * @return mixed sanitized data after scrub
     */

    public function lang($selection, $replace = false)
    {

        $selection = strtoupper($selection);
        $lang = $this->language[$selection];

        if ($replace) {
            if (is_array($replace)) {
                $lang = vsprintf($this->language[$selection], $replace);
            } else {
                $lang = sprintf($this->language[$selection], $replace);
            }

        }

        return $lang;

    }

    /**
     * Shorthand method for running only the data filters
     *
     * @param array $data
     * @param array $filters
     *
     * @return mixed sanitized data after scrub
     */

    public function prepare($data, $filters = [])
    {
        return $this->_filter($data, $filters);
    }

    /**
     * Filter the input data according to the specified filter set
     *
     * @access private
     *
     * @param  mixed $data
     * @param  array $filterset
     *
     * @return mixed
     * @throws Exception When validation methods do not exist.
     */

    //@TODO: make sure all output is sanitized
    //@TODO: this needs to be rewrote
    private function _filter($data, array $filterset)
    {
        if ($data === null) {
            return false;
        }

        $string = false;
        if (is_string($data)) {
            $string = true;
        }

        foreach ($filterset as $field => $filters) {
            if (is_object($data) && !array_key_exists_r($field, $data)) {
                continue;
            }

            $filters = explode('|', $filters);

            foreach ($filters as $filter) {
                $params = false;

                if (strstr($filter, ',') !== false) {
                    $filter = explode(',', $filter);

                    $params = array_slice($filter, 1, count($filter) - 1);

                    $filter = $filter[0];
                }

                if (is_callable(array($this, '_filter' . $filter))) {
                    $method = '_filter' . $filter;

                    if ($string === false) {
                        foreach ($data as $k => $v) {
                            if (is_object($data) || is_object($data[$k])) {
                                if (isset($data->$field) && !is_object($data->$field)) {
                                    $data->$field = $this->$method($data->$field, $params);

                                } else {
                                    $data[$k]->$field = $this->$method($data[$k]->$field, $params);
                                }

                            } elseif (is_array($data)) {
                                if (isset($data[$field]) && !is_array($data[$field])) {
                                    $data[$field] = $this->$method($data[$field], $params);
                                } else {
//

                                    $data[$k][$field] = $this->$method($data[$k][$field], $params);
                                }

                            }
                        }
                    } else {
                        $data = $this->$method($data, $params);
                    }

                } else {
                    throw new Exception("Filter method '$filter' does not exist.");
                }
            }
        }

        return $data;
    }


    /**
     * Replace tidies html for valid html
     *
     * Usage: '<index>' => 'tidy'
     *
     * @access private
     *
     * @param  string $value
     * @param  mixed $params
     *
     * @return string
     */

    private function _filterTidy($value, $params = false)
    {

        $tidy = new \tidy;
        $config = array('indent'         => true,
                        'output-xhtml'   => true,
                        'wrap'           => 200,
                        'clean'          => true,
                        'show-body-only' => true
        );
        $tidy->parseString($value, $config, 'utf8');
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
     *
     * @param  string $value
     * @param  mixed $params
     *
     * @return string
     */
    private function _filterTruncate($value, $params = false)
    {
        $append = '&hellip;';
        $break = " ";
        $limit = $params[0];

        if (strlen($value) <= $limit) {
            return $value;
        }

        if (false !== ($breakpoint = strpos($value, $break, $limit))) {
            if ($breakpoint < strlen($value) - 1) {
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
     *
     * @param  string $value
     * @param  mixed $params
     *
     * @return string
     */
    private function _filterBlacklist($value, $params = false)
    {

        $value = preg_replace('/\s\s+/u', chr(32), $value);

        $value = " $value ";

        $words = explode(',', $this->blacklist);

        foreach ($words as $word) {
            $word = trim($word);

            $word = " $word "; // Normalize

            if (stripos($value, $word) !== false) {
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
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterRemovePunctuation($value)
    {

        return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
    }


    /**
     * Sanitize the string by removing any script tags
     *
     * Usage: '<index>' => 'sanitizestring'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterSanitizeString($value)
    {

        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitize the string by urlencoding characters
     *
     * Usage: '<index>' => 'urlencode'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterURLencode($value)
    {

        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities
     *
     * Usage: '<index>' => 'htmlencode'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterHTMLencode($value)
    {

        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Sanitize the string by removing illegal characters from emails
     *
     * Usage: '<index>' => 'sanitizeemail'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterSanitizeEmail($value)
    {

        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize the string by removing illegal characters from numbers
     *
     * Usage: '<index>' => 'sanitizenumbers'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterSanitizeNumbers($value)
    {

        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Filter out all HTML tags except the defined basic tags
     *
     * Usage: 'index' => 'basictags'
     *
     * @access private
     *
     * @param  string $value
     *
     * @return string
     */
    private function _filterBasicTags($value)
    {

        return strip_tags($value, $this->basictags);
    }
}
