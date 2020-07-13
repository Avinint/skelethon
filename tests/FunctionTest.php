<?php


use PHPUnit\Framework\TestCase;

require dirname(__DIR__).'/functions.php';

class FunctionTest extends TestCase
{
    public function testArrayContainsScalarValues()
    {
        $this->assertTrue(array_contains(4, [2, 3, 4]));
        $this->assertFalse(array_contains(6, [2, 3, 4]));
    }

    public function testArrayContainsAnyElementFromOtherArray()
    {
        $this->assertTrue(array_contains([1, 2, 3], [2, 3, 4]));
    }

    public function testArrayContainsAllElementsFromOtherArray()
    {
        $this->assertFalse(array_contains([1, 2, 3], [2, 3, 4], true));
        $this->assertTrue(array_contains([1, 2, 3], [1, 2, 3, 4], true));
    }

    public function testArrayIsContainedInNestedArray()
    {
        $this->assertTrue(array_contains([1, 2], [[1, 2], 3], true, true));
        $this->assertTrue(array_contains([[1, 2], 3], [[1, 2], 3], true, true));
        $this->assertTrue(array_contains([[1, 2], 3], [[[1, 2], 3], 5], true, true));
        $this->assertTrue(array_contains([1, 2], [[ 3], [1, 2], 5], true, true));

        $this->assertFalse(array_contains([1, 2, 3], [[[1, 2], 3], 5], true, true));
    }

    public function testStrReplaceFirstChangesString()
    {
        $subject = 'Hello world!';
        $this->assertEquals('Hello David!', str_replace_first('world', 'David', $subject), "Error replacing text in string");
    }

    public function testStrReplaceFirstChangesOnlyFirstOccurrenceInString()
    {
        $subject = 'Hello David and David!';
        $this->assertEquals('Hello Hal and David!', str_replace_first('David', 'Hal', $subject), "Error replacing text in string");
    }

    public function testStrReplaceLastChangesString()
    {
        $subject = 'Hello world!';
        $this->assertEquals('Hello David!', str_replace_last('world', 'David', $subject), "Error replacing text in string");
    }

    public function testStrReplaceLastChangesOnlyLastOccurrenceInString()
    {
        $subject = 'Hello David and David!';
        $this->assertEquals('Hello David and Hal!', str_replace_last('David', 'Hal', $subject), "Error replacing text in string");
    }
}