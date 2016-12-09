<?php
namespace Zewa\Interfaces;

/**
 * Interface CollectionInterface
 * @package App\Interfaces
 */
interface CollectionInterface extends \Countable, \ArrayAccess, \Traversable, \JsonSerializable, \IteratorAggregate
{
    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Clear collection contents
     *
     * @return bool
     */
    public function clear();

    /**
     * @param callable $func
     * @return mixed
     */
    public function map(callable $func);

    /**
     * @param callable $func
     * @return mixed
     */
    public function each(callable $func);

    /**
     * @param callable $func
     * @return mixed
     */
    public function filter(callable $func);

    /**
     * @param callable $func
     * @return mixed
     */
    public function not(callable $func);

    /**
     * @param $initial
     * @param callable $func
     * @return mixed
     */
    public function reduce($initial, callable $func);

    /**
     * Returns array of collection
     * @return array
     */
    public function getArray();
}
