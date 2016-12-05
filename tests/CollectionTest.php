<?php


namespace Zewa\Tests;

use Zewa\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCount()
    {
        $collection = new Collection(['one', 'two', 'three']);

        $this->assertSame(3, $collection->count());
    }

    public function testNotEmpty()
    {
        $collection = new Collection(['this', 'collection', 'is', 'not', 'empty']);

        $this->assertFalse($collection->isEmpty());
    }

    public function testIsEmpty()
    {
        $constructedEmpty = new Collection([]);

        $this->assertTrue($constructedEmpty->isEmpty());

        $constructedNotEmptyThenCleared = new Collection(['not', 'empty']);
        $constructedNotEmptyThenCleared->clear();

        $this->assertTrue($constructedNotEmptyThenCleared->isEmpty());
    }

    public function testGetArray()
    {
        $expectedArray = ['this', 'is', 'my', 'expected', 'array'];
        $collectionFromStandardArray = new Collection($expectedArray);

        $this->assertSame($expectedArray, $collectionFromStandardArray->getArray());

        $expectedAssocArray = ['this' => 'is', 'my' => 'expected', 'assoc' => 'array'];
        $collectionFromAssocArray = new Collection($expectedAssocArray);

        $this->assertSame($expectedAssocArray, $collectionFromAssocArray->getArray());
    }

    /**
     * The Collection class should implement the JsonSerializable interface
     * http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * so we could actually test the use of json_encode() rather than pretending
     * this is just the same as getArray() since that is all jsonSerialize() does
     * right now. =(
     */
    public function testJsonSerialize()
    {
        $expectedArray = ['this', 'is', 'my', 'expected', 'array'];
        $collectionFromStandardArray = new Collection($expectedArray);

        $this->assertSame($expectedArray, $collectionFromStandardArray->jsonSerialize());
    }

    public function testGetIterator()
    {
        $collection = new Collection(['this', 'is', 'an', 'array']);

        $this->assertInstanceOf('ArrayIterator', $collection->getIterator());
    }

    public function testOffsetExists()
    {
        $collection = new Collection(['thisOffset' => 'exists', 'thatOffset' => 'exists']);

        $this->assertTrue($collection->offsetExists('thisOffset'));
        $this->assertFalse($collection->offsetExists('nonExistantOffset'));
    }

    public function testOffsetGet()
    {
        $collection = new Collection(['thisOffset' => 'hasABigValue']);

        $this->assertSame('hasABigValue', $collection->offsetGet('thisOffset'));
        $this->assertTrue(is_null($collection->offsetGet('nonExistantOffset')));
    }

    public function testOffsetSet()
    {
        $collection = new Collection([]);
        $collection->offsetSet('thisOffset', 'hasABigValue');

        $this->assertTrue($collection->offsetExists('thisOffset'));
    }

    public function testOffsetUnset()
    {
        $collection = new Collection(['thisOffset' => 'hasABigValue']);

        $collection->offsetUnset('thisOffset');

        $this->assertFalse($collection->offsetExists('thisOffset'));
        $this->assertTrue($collection->isEmpty());
    }

    public function testMap()
    {
        $collection = new Collection(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $collection->map(function ($value) {
            return $value . "_appendToValue";
        });

        $expectedResult = ['a' => 'a_appendToValue', 'b' => 'b_appendToValue', 'c' => 'c_appendToValue'];

        $this->assertSame($expectedResult, $collection->getArray());
    }

    public function testFilter()
    {
        $collection = new Collection(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $collection->filter(function ($key, $value) {
            if ($key === 'a' || $value === 'a') {
                return false;
            }

            return true;
        });

        $expectedResult = ['b' => 'b', 'c' => 'c'];

        $this->assertSame($expectedResult, $collection->getArray());
    }

    public function testEach()
    {
        $expectedResult = ['a' => 'a', 'b' => 'b', 'c' => 'c'];
        $collection = new Collection($expectedResult);

        $actualResult = [];
        $collection->each(function ($key, $value) use (&$actualResult) {
            $actualResult[$key] = $value;
        });

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testNot()
    {
        $collection = new Collection(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $collection->not(function ($key, $value) {
            if ($key === 'a' || $value === 'a') {
                return false;
            }

            return true;
        });

        $expectedResult = ['a' => 'a'];

        $this->assertSame($expectedResult, $collection->getArray());
    }

    public function testStringReduce()
    {
        $collection = new Collection(['this', 'will', 'be', 'reduced']);

        $reduceResult = $collection->reduce('', function ($accumulator, $value) {
            return $accumulator . ' ' . $value;
        });

        $this->assertSame(' this will be reduced', $reduceResult);
    }

    public function testNumericReduce()
    {
        $collection = new Collection(['1', '1', '1', '1']);

        $reduceResult = $collection->reduce(0, function ($accumulator, $value) {
            return $accumulator + $value;
        });

        $this->assertSame(4, $reduceResult);
    }
}
