<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Test\Unit\Model\Adapter;

use \PHPUnit\Framework\TestCase;

/**
 * Class ClearpayOrderTokenTest
 * Includes Sample assertions
 */
class ClearpayOrderTokenTest extends TestCase
{
    //Sample assertions

    //Additions of 2 numbers
    public function testAdd()
    {
        $a = 7;
        $b = 5;
        $expected = 12;
        $this->assertEquals($expected, $a + $b);
    }

    //Stack Push and Pop
    public function testPushAndPop()
    {
        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}
