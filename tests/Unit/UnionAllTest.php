<?php
use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;

class UnionAllTest extends TestCase
{
  public function testUnionAllEmptyArrays()
  {
    // Test case 1: Empty arrays
    $data1 = [];
    $data2 = [];
    $result = LinqPHP::from($data1)->unionAll($data2)->toArray();

    $this->assertEquals([], $result);
  }

  public function testUnionAllWithCommonKeys()
  {
    // Test case 2: Non-empty arrays with common keys
    $data1 = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
    ];
    $data2 = [
      ['id' => 1, 'age' => 25],
      ['id' => 3, 'age' => 30],
    ];

    $expected = [
      ['id' => 1, 'name' => null, 'age' => 25],
      ['id' => 1, 'name' => 'John', 'age' => null],
      ['id' => 2, 'name' => 'Jane', 'age' => null],
      ['id' => 3, 'name' => null, 'age' => 30],
    ];

    $result = LinqPHP::from($data1)->unionAll($data2)
      ->orderBy(['id', 'name', 'age'])->toArray();
    $this->assertEquals($expected, $result);
  }

  public function testUnionAllWithDifferentKeys()
  {
    // Test case 3: Non-empty arrays with no common keys
    $data1 = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
    ];
    $data2 = [
      ['id' => 3, 'age' => 30],
      ['id' => 4, 'age' => 40],
    ];
    $expected = [
      ['id' => 1, 'name' => 'John', 'age' => null],
      ['id' => 2, 'name' => 'Jane', 'age' => null],
      ['id' => 3, 'name' => null, 'age' => 30],
      ['id' => 4, 'name' => null, 'age' => 40],
    ];

    $result = LinqPHP::from($data1)->unionAll($data2)->toArray();
    $this->assertEquals($expected, $result);
  }
}