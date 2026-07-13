<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;

class DistinctTest extends TestCase
{
  public function testDistinct()
  {
    // Test distinct with an array of unique elements
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
    ];
    $expected = $data;
    $result = LinqPHP::from($data)->distinct()->toArray();
    $this->assertEquals($expected, $result);

    // Test distinct with an array of duplicate elements
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
      ['id' => 1, 'name' => 'John', 'age' => 25],
    ];
    $expected = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30]
    ];
    $result = LinqPHP::from($data)->distinct()->toArray();
    $this->assertEquals($expected, $result);

    // Test distinct with an array of nested objects
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
      ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
    ];
    $expected = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']]
    ];
    $result = LinqPHP::from($data)->distinct()->toArray();
    $this->assertEquals($expected, $result);
  }
}
