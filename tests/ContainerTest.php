<?php

namespace Zewa\Config\Tests;

use Zewa\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigItem()
    {
        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);

        $expectedModuleConfig = require $configPath . "/Modules.php";
        $this->assertSame($expectedModuleConfig, $config->get('modules'));
    }

    /**
     * APP_PATH is set in the test bootstrap.php
     */
    public function testLoadConfigFromGlobalData()
    {
        // New Config without passing config folder path generates path from APP_PATH global
        $config = new Config();

        $expectedModuleConfig = require APP_PATH . "/Config/Modules.php";
        $this->assertSame($expectedModuleConfig, $config->get('modules'));
    }

    /**
     * @expectedException \Zewa\Exception\ConfigException
     */
    public function testLoadNonExistentConfigThrowsException()
    {
        $configPath = __DIR__ . "/fixtures/app/Config";
        $config = new Config($configPath);

        return $config->get('no_config_file_by_this_name');
    }

    public function testSetGetConfigItem()
    {
        $config = new Config();

        $configItem = ['oneSetting' => 'oneValue', 'twoSettings' => 'twoValues'];
        $config->set('testConfigKey', $configItem);

        $this->assertSame($configItem, $config->get('testConfigKey'));
    }
}