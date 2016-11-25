<?php
namespace Zewa\Interfaces\HTTP;

/**
 * Interface ContainerInterface
 * @package Zewa\Interfaces
 */
interface GlobalInterface
{
    public function set(string $key, $value);

    public function fetch(string $key);

    public function remove(string $key);
}
