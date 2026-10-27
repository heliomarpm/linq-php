<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class GroupByTest extends TestCase
{
    public function testGroupBySum()
    {
        $data = [
            ['category' => 'A', 'value' => 10],
            ['category' => 'A', 'value' => 20],
            ['category' => 'B', 'value' => 5],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['category'], ['sum' => ['value']])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(30, $result[0]['value']);
    }

    public function testGroupByDefaultAggregations()
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25],
            ['id' => 2, 'name' => 'Jane', 'age' => 30],
            ['id' => 3, 'name' => 'John', 'age' => 35],
        ];
        $groupKey = 'John';

        $result = LinqPHP::from($data)
            ->groupBy(['name'], [])
            ->toArray();

        $this->assertEquals($groupKey, $result[0]['name']);
        $this->assertEquals(60, $result[0]['age']);
    }

    public function testGroupByCustomAggregations()
    {
        $data = [
            ['category' => 'A', 'value' => 10],
            ['category' => 'A', 'value' => 20],
            ['category' => 'B', 'value' => 5],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['category'], ['sum' => ['value']])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(30, $result[0]['value']);
    }

    public function testGroupByWithSum()
    {
        $data = [
            ['g' => 'A', 'v1' => 10, 'v2' => 5],
            ['g' => 'A', 'v1' => 30, 'v2' => 15],
            ['g' => 'B', 'v1' => 20, 'v2' => 7],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['g'], [
                'sum' => ['v1'],
            ])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(40.0, $result[0]['v1']);
    }

    public function testGroupByWithMax()
    {
        $data = [
            ['g' => 'A', 'v1' => 10, 'v2' => 5],
            ['g' => 'A', 'v1' => 30, 'v2' => 15],
            ['g' => 'B', 'v1' => 20, 'v2' => 7],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['g'], [
                'max' => ['v2'],
            ])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(15, $result[0]['v2']);
    }

    public function testGroupByWithMin()
    {
        $data = [
            ['g' => 'A', 'v1' => 10, 'v2' => 5],
            ['g' => 'A', 'v1' => 30, 'v2' => 15],
            ['g' => 'B', 'v1' => 20, 'v2' => 7],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['g'], [
                'min' => ['v2'],
            ])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(5, $result[0]['v2']);
    }

    public function testGroupByWithAvg()
    {
        $data = [
            ['g' => 'A', 'v1' => 10, 'v2' => 5],
            ['g' => 'A', 'v1' => 30, 'v2' => 15],
            ['g' => 'B', 'v1' => 20, 'v2' => 7],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['g'], [
                'avg' => ['v1'],
            ])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(20.0, $result[0]['v1']);
    }

    public function testGroupByWithMultipleAggregationsAndAvg()
    {
        $data = [
            ['g' => 'A', 'v1' => 10, 'v2' => 5],
            ['g' => 'A', 'v1' => 30, 'v2' => 15],
            ['g' => 'B', 'v1' => 20, 'v2' => 7],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['g'], [
                'sum' => ['v1'],
                'max' => ['v2'],
                'min' => ['v2'],
                'avg' => ['v1'],
            ])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(40.0, $result[0]['v1_(sum)']);
        $this->assertEquals(15, $result[0]['v2_(max)']);
        $this->assertEquals(5, $result[0]['v2_(min)']);
        $this->assertEquals(20.0, $result[0]['v1_(avg)']);
    }

    public function testGroupByMultiKeyWithoutCollisionOnUnderscore()
    {
        $data = [
            ['uf' => 'SP_CENTRO', 'regiao' => 'SUL', 'valor' => 100],
            ['uf' => 'SP', 'regiao' => 'CENTRO_SUL', 'valor' => 200],
            ['uf' => 'SP_CENTRO', 'regiao' => 'SUL', 'valor' => 50],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['uf', 'regiao'], ['sum' => ['valor']])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals('SP_CENTRO', $result[0]['uf']);
        $this->assertEquals('SUL', $result[0]['regiao']);
        $this->assertEquals(150, $result[0]['valor']);

        $this->assertEquals('SP', $result[1]['uf']);
        $this->assertEquals('CENTRO_SUL', $result[1]['regiao']);
        $this->assertEquals(200, $result[1]['valor']);
    }

    public function testGroupByMultiKeyWithDifferentKeyOrdersInItems()
    {
        $data = [
            ['uf' => 'SP', 'tipo' => 'PJ', 'valor' => 100],
            ['tipo' => 'PJ', 'uf' => 'SP', 'valor' => 200],
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['uf', 'tipo'], ['sum' => ['valor']])
            ->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals('SP', $result[0]['uf']);
        $this->assertEquals('PJ', $result[0]['tipo']);
        $this->assertEquals(300, $result[0]['valor']);
    }

    public function testGroupByMultiKeyWithMissingFieldsInSomeItems()
    {
        $data = [
            ['uf' => 'SP', 'tipo' => 'PJ', 'valor' => 100],
            ['uf' => 'SP', 'valor' => 50], // tipo is null/missing
            ['tipo' => 'PJ', 'valor' => 25], // uf is null/missing
        ];

        $result = LinqPHP::from($data)
            ->groupBy(['uf', 'tipo'], ['sum' => ['valor']])
            ->toArray();

        $this->assertCount(3, $result);
    }
}
