<?php
namespace Zewa\Interfaces;

/**
 * Interface ContainerInterface
 * @package Zewa\Interfaces
 */
interface ContainerInterface
{
    /**
     * @param string $alias
     * @return mixed
     */
    public function get(string $alias);

    /**
     * @param string $alias
     * @param callable $class
     * @param bool $retain
     */
    public function add(string $alias, callable $class, bool $retain = false);
}
