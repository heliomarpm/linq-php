<?php
use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;


class SelectTest extends TestCase
{
  public function testSelectStrictThrowsException()
  {
    $this->expectException(InvalidArgumentException::class);

    LinqPHP::from([
      ['id' => 1, 'name' => 'John']
    ])->select(['gender'], strict: true)->toArray();
  }

  public function testSelectReturnsExpectedResult()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
      ['id' => 3, 'name' => 'John', 'age' => 35],
    ];

    $expectedResult = [
      ['name' => 'John', 'age' => 25],
      ['name' => 'Jane', 'age' => 30],
      ['name' => 'John', 'age' => 35],
    ];

    $result = LinqPHP::from($data)->select(['name', 'age'])->toArray();

    $this->assertEquals($expectedResult, $result);
  }

  public function testSelectReturnsEmptyArrayWhenNoMatchingKeys()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
      ['id' => 3, 'name' => 'John', 'age' => 35],
    ];

    $expectedResult = [
      ['id' => 1, 'gender' => null, 'hobby' => null],
      ['id' => 2, 'gender' => null, 'hobby' => null],
      ['id' => 3, 'gender' => null, 'hobby' => null],
    ];

    $result = LinqPHP::from($data)->select(['id', 'gender', 'hobby'], false)->toArray();
    $this->assertEquals($expectedResult, $result);
  }

  public function testSelectReturnsOriginalDataWhenNoSelectorsProvided()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
      ['id' => 3, 'name' => 'John', 'age' => 35],
    ];

    $result = LinqPHP::from($data)->select([])->toArray();

    $this->assertEquals($data, $result);
  }

  public function testSelectWithDistinct()
  {
    // Test distinct with an array of unique elements
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
    ];
    $expected = $data;
    $result = LinqPHP::from($data)->select([])->distinct()->toArray();
    $this->assertEquals($expected, $result);

    // Test distinct with an array of duplicate elements
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
      ['id' => 1, 'name' => 'John', 'age' => 25],
    ];
    $expected = [
      ['name' => 'John', 'age' => 25],
      ['name' => 'Jane', 'age' => 30],
    ];
    $result = LinqPHP::from($data)->select(['name', 'age'])->distinct()->toArray();
    $this->assertEquals($expected, $result);

    // Test distinct with an array of nested objects
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
      ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
    ];
    $expected = [
      ['address' => ['street' => '123 Main St', 'city' => 'New York']],
      ['address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
    ];
    $result = LinqPHP::from($data)->select(['address'])->distinct()->toArray();
    $this->assertEquals($expected, $result);
  }
}
