<?php
declare(strict_types=1);

namespace Zewa;

use Zewa\Interfaces\CollectionInterface;

/**
 * Class Collection
 * @package App\Models
 */
class Collection implements CollectionInterface
{
    /**
     * @var array
     */
    public $collection = [];

    /**
     * If $data is passed, populate collection
     *
     * @param $data array
     * @access public
     */
    public function __construct(array $data)
    {
        $this->collection = $data;
    }

    public function count() : int
    {
        return count($this->collection);
    }

    public function isEmpty() : bool
    {
        if (!empty($this->collection)) {
            return false;
        }

        return true;
    }

    public function getArray() : array
    {
        return $this->collection;
    }

    public function jsonSerialize() : array //@TODO: this should return text i think
    {
        return $this->collection;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->collection);
    }

    public function offsetGet($offset)
    {
        return $this->collection[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->collection[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->collection[$offset]);
        }
    }

    public function clear()
    {
        $this->collection = [];
    }

    public function mutate($mutation)
    {
        $result = [];
        foreach ($this->collection as $item) {
            $result[] = new $mutation($item);
        }
        $this->clear();
        $this->collection = $result;
    }

    public function map(callable $func)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $func($item);
        }

        $this->clear();
        $this->collection = $result;
    }

    public function filter(callable $func)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            if ($func($key, $item)) {
                $result[$key] = $item;
            }
        }

        $this->clear();
        $this->collection = $result;
    }

    public function each(callable $func)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $func($key, $item);
        }
    }

    public function not(callable $func)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            if (! $func($key, $item)) {
                $result[$key] = $item;
            }
        }

        $this->clear();
        $this->collection = $result;
    }

    public function reduce($initial, callable $func)
    {
        $accumulator = $initial;

        foreach ($this->collection as $item) {
            $accumulator = $func($accumulator, $item);
        }

        return $accumulator;
    }
}
