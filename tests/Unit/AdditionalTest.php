<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;

class AdditionalTest extends TestCase
{
  public function testUnionAllMergesAndFillsNulls()
  {
    $a = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
    ];
    $b = [
      ['id' => 1, 'age' => 25],
      ['id' => 3, 'age' => 30],
    ];

    $result = LinqPHP::from($a)->unionAll($b)->toArray();

    $this->assertCount(4, $result);
    $this->assertSame(['id' => 1, 'name' => 'John', 'age' => null], $result[0]);
    $this->assertSame(['id' => 2, 'name' => 'Jane', 'age' => null], $result[1]);
    $this->assertSame(['id' => 1, 'name' => null, 'age' => 25], $result[2]);
    $this->assertSame(['id' => 3, 'name' => null, 'age' => 30], $result[3]);
  }

  public function testSelectKeepsOnlyGivenKeys()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'age' => 25],
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
    ];

    $result = LinqPHP::from($data)->select(['name'])->toArray();

    $this->assertSame([
      ['name' => 'John'],
      ['name' => 'Jane'],
    ], $result);
  }

  public function testDistinctRemovesDuplicateRows()
  {
    $data = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
    ];

    $result = LinqPHP::from($data)->distinct()->toArray();

    $this->assertCount(2, $result);
    $this->assertContains(['id' => 1, 'name' => 'John'], $result);
    $this->assertContains(['id' => 2, 'name' => 'Jane'], $result);
  }


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
      ['id' => 2, 'name' => 'Jane', 'age' => 30],
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
      ['id' => 2, 'name' => 'Jane', 'age' => 30, 'address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
    ];
    $result = LinqPHP::from($data)->distinct('address')->toArray();
    $this->assertEquals($expected, $result);
  }


  public function testTakeLimitsNumberOfItems()
  {
    $data = [
      ['id' => 1],
      ['id' => 2],
      ['id' => 3],
      ['id' => 4],
    ];

    $result = LinqPHP::from($data)->take(2)->toArray();

    $this->assertSame([
      ['id' => 1],
      ['id' => 2]
    ], $result);
  }



  public function testTakeReturnsSubsetOfData()
  {
    $data = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
      ['id' => 3, 'name' => 'John'],
    ];

    $instance = new LinqPHP($data);
    $result = $instance->take(2)->toArray();

    $this->assertEquals(
      [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']],
      $result
    );
  }

  public function testTakeReturnsEmptyArrayWhenMaxItemsIsZero()
  {
    $data = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
      ['id' => 3, 'name' => 'John'],
    ];

    $instance = new LinqPHP($data);
    $result = $instance->take(0)->toArray();

    $this->assertEquals([], $result);
  }

  public function testTakeReturnsAllDataWhenMaxItemsIsGreaterThanArrayLength()
  {
    $data = [
      ['id' => 1, 'name' => 'John'],
      ['id' => 2, 'name' => 'Jane'],
      ['id' => 3, 'name' => 'John'],
    ];

    $instance = new LinqPHP($data);
    $result = $instance->take(10)->toArray();

    $this->assertEquals($data, $result);
  }

  public function testOrderByKeyAscAndDesc()
  {
    $data = [
      ['id' => 2],
      ['id' => 1],
      ['id' => 3],
    ];

    $asc = LinqPHP::from($data)->orderByKey('id', 'asc')->toArray();
    $this->assertSame([['id' => 1], ['id' => 2], ['id' => 3]], $asc);

    $desc = LinqPHP::from($data)->orderByKey('id', 'desc')->toArray();
    $this->assertSame([['id' => 3], ['id' => 2], ['id' => 1]], $desc);
  }

  public function testOrderByMultipleFields()
  {
    $data = [
      ['name' => 'John', 'age' => 30],
      ['name' => 'John', 'age' => 25],
      ['name' => 'Jane', 'age' => 35],
    ];

    $result = LinqPHP::from($data)->orderBy(['name' => 'asc', 'age' => 'desc'])->toArray();

    $this->assertSame([
      ['name' => 'Jane', 'age' => 35],
      ['name' => 'John', 'age' => 30],
      ['name' => 'John', 'age' => 25],
    ], $result);
  }

  public function testToObjectReturnsElapsedCountAndRows()
  {
    $data = [['id' => 1], ['id' => 2]];
    $obj = LinqPHP::from($data)->toObject();

    $this->assertIsObject($obj);
    $this->assertTrue(property_exists($obj, 'elapsedTime'));
    $this->assertTrue(property_exists($obj, 'count'));
    $this->assertTrue(property_exists($obj, 'data'));
    $this->assertSame(2, $obj->count);
    $this->assertSame($data, $obj->data);
    $this->assertGreaterThanOrEqual(0, (float) $obj->elapsedTime);
  }

  public function testJoinRightAndFullOuter()
  {
    $a = [
      ['id' => 1],
    ];
    $b = [
      ['id' => 2],
    ];

    $right = LinqPHP::from($a)->join($b, 'RIGHT', ['id'])->toArray();
    $this->assertCount(1, $right);
    $this->assertSame(['id' => 2], $right[0]);

    $full = LinqPHP::from($a)->join($b, 'FULL', ['id'])->toArray();
    $this->assertCount(2, $full);
    $this->assertContains(['id' => 1], $full);
    $this->assertContains(['id' => 2], $full);
  }

  public function testWhereWithCaseInsensitiveOperators()
  {
    $data = [
      ['name' => 'Alpha'],
      ['name' => 'beta'],
      ['name' => 'Gamma'],
    ];

    $starts = LinqPHP::from($data)->where(['name', 'startswith', 'a'])->toArray();
    $this->assertSame([['name' => 'Alpha']], $starts);

    $contains = LinqPHP::from($data)->where(['name', 'contains', 'AM'])->toArray();
    $this->assertSame([['name' => 'Gamma']], $contains);

    $ends = LinqPHP::from($data)->where(['name', 'endswith', 'TA'])->toArray();
    $this->assertSame([['name' => 'beta']], $ends);
  }

  public function testGroupByWithMultipleAggregationsAndAvg()
  {
    $data = [
      ['g' => 'A', 'v1' => 10, 'v2' => 5],
      ['g' => 'A', 'v1' => 30, 'v2' => 15],
      ['g' => 'B', 'v1' => 20, 'v2' => 7],
    ];

    $res = LinqPHP::from($data)
      ->groupBy(['g'], [
        'sum' => ['v1'],
        'max' => ['v2'],
        'min' => ['v2'],
        'avg' => ['v1']
      ])
      ->toArray();

    $this->assertCount(2, $res);
    // Grupo A
    $a = array_values(array_filter($res, fn($x) => $x['g'] === 'A'))[0];
    $this->assertEquals(40.0, $a['v1_(sum)']);
    $this->assertEquals(15, $a['v2_(max)']);
    $this->assertEquals(5, $a['v2_(min)']);
    $this->assertEquals(20.0, $a['v1_(avg)']);
  }
}