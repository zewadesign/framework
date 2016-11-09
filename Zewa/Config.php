<?php
namespace Zewa;

use Zewa\Exception\ConfigException;

class Config
{
    /**
     * Loaded Configuration Items
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Path to Configuration Folder
     *
     * @var string
     */
    protected $path;

    /**
     * Configuration file extension
     */
    const CONFIG_FILE_EXTENSION = ".php";

    /**
     * Config constructor.
     *
     * @param $configFolderPath
     */
    public function __construct($configFolderPath)
    {
        $this->setPath($configFolderPath);
    }

    /**
     * Sets the configuration folder path
     *
     * @param $path
     */
    protected function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Loads a configuration file in to memory
     *
     * @param $key
     * @return bool
     */
    protected function loadConfigFile(string $key) : bool
    {
        $key = strtolower($key);
        $filename = $this->path . DIRECTORY_SEPARATOR . ucfirst($key) . Config::CONFIG_FILE_EXTENSION;

        if (file_exists($filename)) {
            $this->configuration[$key] = require $filename;
            return true;
        }

        return false;
    }

    /**
     * Get Configuration Item
     *
     * @param $key
     *
     * @return \stdClass
     * @throws ConfigException when config file not found
     */
    public function get(string $key)
    {
        $key = strtolower($key);
        $value = [];

        if (isset($this->configuration[$key]) || $this->loadConfigFile($key)) {
            $value = $this->configuration[$key];
        } else {
            throw new ConfigException($key . ' configuration file is missing.');
        }

        return $value;
    }

    /**
     * @param string $key
     * @param $value mixed array|string
     */
    public function set(string $key, $value)
    {
        $key = strtolower($key);
        $this->configuration[$key] = $value;
    }
}
