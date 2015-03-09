<?php
namespace core;

use \Exception as Exception;

/**
 * Registry handling of key/value pairs
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class Registry
{
    /**
     * Reference to static registry array.
     *
     * @var object
     */
    private static $registry = [];

    /**
     * Get a value from the registry
     *
     * @access public
     *
     * @param  string $key
     *
     * @return mixed
     */
    public static function get($key)
    {
        if (self::exists($key)) {
            return self::$registry[$key];
        }

        return false;
    }

    /**
     * Add javascript to load the registry
     *
     * @access public
     *
     * @param  string $script path/to/script
     */
    public static function addJS($script)
    {
        if (!array_search($script, self::$registry['_scripts'])) {
            self::$registry['_scripts'] = array_push(self::$registry['_scripts'], $script);
        }
    }

    /**
     * Get all values from the registry
     *
     * @access public
     * @return array
     */
    public static function getAll()
    {
        return self::$registry;
    }

    /**
     * Add value to the registry
     *
     * @access public
     *
     * @param string $key
     * @param mixed $value
     * @param boolean $replace
     *
     * @throws Exception when key is set and replace is false.
     */
    public static function add($key, $value, $replace = true)
    {
        if (self::exists($key) && $replace === false) {
            throw new Exception($key . ' already set. Use replace method.');
        }

        self::$registry[$key] = $value;
    }

    /**
     * Omit value from the registry
     *
     * @access public
     *
     * @param string $key
     */
    public static function remove($key)
    {
        if (!is_array($key) && self::exists($key)) {
            unset(self::$registry[$key]);
        }
    }

    /**
     * Dumps entire registry
     *
     * @access public
     */
    public static function clear()
    {
        self::$registry = [];
    }

    /**
     * Checks if a value is present in the registry
     *
     * @access public
     *
     * @param string $key
     *
     * @return boolean
     */
    public static function exists($key)
    {
        return isset(self::$registry[$key]);
    }
}
