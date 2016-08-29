<?php


namespace Zewa\Config;


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
     *
     * @throws ConfigException
     */
    protected function loadConfigFile($key)
    {
        if (!file_exists($this->path . "/" . $key . Config::CONFIG_FILE_EXTENSION)) {
            throw new ConfigException(
                'Configuration file does not exist: '
                . $this->path . "/" . $key . Config::CONFIG_FILE_EXTENSION
            );
        }

        $this->configuration[$key] = require $this->path . "/" . $key . Config::CONFIG_FILE_EXTENSION;
    }

    /**
     * Get Configuration Item
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->configuration[$key])) {
            $this->loadConfigFile($key);
        }

        return $this->configuration[$key];
    }
}