<?php
/**
 * Tests for the Security class.
 *
 * As of writing this, the Security class only has one method 'normalize'
 * which does more than one thing so it should be refactored.  The class
 * can definitely be refactored without breaking these tests.
 */

namespace Zewa\Tests;

use Zewa\Security;

class SecurityTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeNullIsNull()
    {
        $security = new Security();

        $this->assertTrue(is_null($security->normalize(null)));
    }

    /**
     * @dataProvider stringProvider
     *
     * @param string $stringValue
     */
    public function testStringsAreNotModified($stringValue)
    {
        $security = new Security();

        $this->assertSame($stringValue, $security->normalize($stringValue));
    }

    public function stringProvider()
    {
        return [
            ['string', '012345']
        ];
    }

    /**
     * @dataProvider integerProvider
     *
     * @param $integerValue
     */
    public function testIntegersAreNotModified($integerValue)
    {
        $security = new Security();

        $this->assertSame($integerValue, $security->normalize($integerValue));
    }

    public function integerProvider()
    {
        return [
            [1, 10, 999, 123456789, -1, -10, -999, -123456789]
        ];
    }

    /**
     * @dataProvider stringIntegerProvider
     *
     * @param $stringInteger
     */
    public function testStringBecomesInteger($stringInteger)
    {
        $security = new Security();

        $integerResult = $security->normalize($stringInteger);

        // It should still be the same value.
        $this->assertTrue(($stringInteger == $integerResult));
        // But not the same type
        $this->assertFalse(($stringInteger === $integerResult));
        // Because it's now an integer instead of a string.
        $this->assertTrue(is_integer($integerResult));
    }

    public function stringIntegerProvider()
    {
        return [
            ["1", "10", "999", "123456789", "-1", "-10", "-999", "-123456789"]
        ];
    }

    /**
     * @dataProvider floatProvider
     *
     * @param $floatValue
     */
    public function testFloatsAreNotModified($floatValue)
    {
        $security = new Security();

        $this->assertSame($floatValue, $security->normalize($floatValue));
    }

    public function floatProvider()
    {
        return [
            [.023, .3333333333, 1.1, .314, -.023, -.3333333, -1.1, -.314]
        ];
    }

    public function testAssocArrayOfStringsAreNotModified()
    {
        $assocArrayOfStrings = [
            'stringKey'    => 'stringValue',
            'someOtherKey' => 'someOtherValue',
        ];

        $security = new Security();

        $this->assertSame($assocArrayOfStrings, $security->normalize($assocArrayOfStrings));
    }

    public function testObjectStaysObject()
    {
        $object = new \stdClass();
        $object->property = 'stringValue';
        $object->someOtherProperty = 'SomeOtherStringValue';

        $security = new Security();

        $this->assertInstanceOf('stdClass', $security->normalize($object));
    }
}
