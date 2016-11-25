<?php
declare(strict_types=1);
namespace Zewa;

use Zewa\Exception\LookupException;

class Container
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * Add an object to the container
     *
     * @param string $key The name of a service to set in the container
     * @param mixed $value A closure of object representing a serive
     * @return $this
     */
    public function set($key, $value = null)
    {
        $this->container[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     * @throws LookupException
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new LookupException('Container doesn\'t exist.');
        }

        return $this->container[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->container);
    }
}
