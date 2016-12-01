<?php
declare(strict_types=1);
namespace Zewa;

/**
 * Abstract class for controller extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
abstract class Controller
{
    /**
     * System configuration
     *
     * @var Config
     */
    protected $configuration;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
    }
}
