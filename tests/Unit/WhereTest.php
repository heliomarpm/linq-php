<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;

class WhereTest extends TestCase
{
  public function testWhereWithSimpleCondition()
  {
    $data = [
      ['id' => 1, 'age' => 20],
      ['id' => 2, 'age' => 30],
    ];

    $result = LinqPHP::from($data)
      ->where(['age', '>', 25])
      ->toArray();

    $this->assertCount(1, $result);
    $this->assertEquals(2, $result[0]['id']);
  }

  public function testWhereWithClosure()
  {
    $data = [
      ['id' => 1],
      ['id' => 2],
      ['id' => 3],
    ];

    $result = LinqPHP::from($data)
      ->where(fn($x) => $x['id'] % 2 === 0)
      ->toArray();

    $this->assertEquals([['id' => 2]], $result);
  }

  public function testWhereWithArrayCondition()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
      ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
    ];

    $result = LinqPHP::from($data)
      ->where([
        ['age', '>=', 30],
        ['status', '=', 'pending'],
      ])->toArray();

    $expectedResult = [
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
    ];

    $this->assertEquals($expectedResult, $result);
  }

  public function testWhereWithFunctionCondition()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
      ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
    ];

    $linq = new LinqPHP($data);

    $result = $linq->where(fn($item) => $item['age'] > 30)->toArray();

    $expectedResult = [
      ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
    ];

    $this->assertEquals($expectedResult, $result);
  }

  public function testWhereWithMixedConditions()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
      ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
    ];

    $linq = new LinqPHP($data);

    $result = $linq->where([
      ['age', '>', 25],
      fn($item) => $item['status'] === 'pending',
    ])->toArray();

    $expectedResult = [
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
    ];

    $this->assertEquals($expectedResult, $result);
  }

}
