<?php
declare(strict_types = 1);
namespace Zewa;

use Interop\Container\ContainerInterface;
use Zewa\Exception\LookupException;

class Container implements ContainerInterface
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
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        $this->container[$key] = $value;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->container[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (! $this->has($key)) {
            throw new LookupException('Container doesn\'t exist.');
        }

        return $this->container[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists($key, $this->container);
    }
}
