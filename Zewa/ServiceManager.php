<?php
namespace Zewa;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class ServiceManager
{

    private $services = [];

    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
        $database = function () {
            return new Database();
        };

        $router = function () {
            return new Router();
        };

        $request = function () {
            return new Request();
        };

        $this->services['database'] = $database();
        $this->services['router'] = $router();
        $this->services['request'] = $request();
    }

    public function __get($property)
    {
        if (! empty ( $this->services[$property] )) {
            return $this->services[$property];
        }
        throw new Exception\LookupException('The service: ' . $property . ' hasn\'t been registered.');
    }

    public function __set($property, $value)
    {
        if (is_callable($value)) {
            $this->services[$property] = $value;
            return true;
        }

        throw new Exception\TypeException('You must provide a callable resource when providing a service manager');
    }
}
