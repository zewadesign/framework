<?php


namespace Zewa\Config\Tests;

use Zewa\Config\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigItem()
    {
        $configPath = __DIR__ . "/../fixtures/app/Config";
        $config = new Config($configPath);

        $expectedModuleConfig = require __DIR__ . "/../fixtures/app/Config/modules.php";
        $this->assertSame($expectedModuleConfig,$config->get('modules'));
    }

    /**
     * @expectedException \Zewa\Config\ConfigException
     */
    public function testLoadNonExistentConfigThrowsException()
    {
        $configPath = __DIR__ . "/../fixtures/app/Config";
        $config = new Config($configPath);
        return $config->get('no_config_file_by_this_name');
    }
}
