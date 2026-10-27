<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

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

    public function testWhereWithCaseInsensitiveUtf8Accents(): void
    {
        $data = [
            ['id' => 1, 'nome' => 'JOÃO SILVA', 'cidade' => 'SÃO PAULO'],
            ['id' => 2, 'nome' => 'Maçã Fresca', 'item' => 'ÁGUA MINERAL'],
        ];

        // Comparação com acentuação UTF-8 case-insensitive padrão
        $result = LinqPHP::from($data)
            ->where(['nome', '=', 'joão silva'])
            ->toArray();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);

        $resultContains = LinqPHP::from($data)
            ->where(['item', 'contains', 'água'])
            ->toArray();

        $this->assertCount(1, $resultContains);
        $this->assertSame(2, $resultContains[0]['id']);

        $resultIn = LinqPHP::from($data)
            ->where(['nome', 'in', ['maçã fresca', 'outro']])
            ->toArray();

        $this->assertCount(1, $resultIn);
        $this->assertSame(2, $resultIn[0]['id']);
    }

    public function testWhereWithCaseSensitiveExactMatch(): void
    {
        $data = [
            ['id' => 1, 'token' => 'TokenABC'],
            ['id' => 2, 'token' => 'tokenabc'],
        ];

        // Com caseSensitive: true, apenas o item com o case idêntico é retornado
        $resultUpper = LinqPHP::from($data)
            ->where(['token', '=', 'TokenABC'], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $resultUpper);
        $this->assertSame(1, $resultUpper[0]['id']);

        $resultLower = LinqPHP::from($data)
            ->where(['token', '=', 'tokenabc'], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $resultLower);
        $this->assertSame(2, $resultLower[0]['id']);

        // Com caseSensitive: false (padrão), ambos combinam com a busca
        $resultDefault = LinqPHP::from($data)
            ->where(['token', '=', 'TOKENABC'])
            ->toArray();

        $this->assertCount(2, $resultDefault);
    }

    public function testWhereWithCaseSensitiveOperators(): void
    {
        $data = [
            ['id' => 1, 'sku' => 'PROD-A-100'],
            ['id' => 2, 'sku' => 'prod-b-200'],
        ];

        // startswith sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'startswith', 'PROD-A'], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'startswith', 'prod-a'], caseSensitive: true)->toArray());

        // contains sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'contains', 'A-100'], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'contains', 'a-100'], caseSensitive: true)->toArray());

        // endswith sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'endswith', '200'], caseSensitive: true)->toArray());

        // in sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'in', ['PROD-A-100', 'INEXISTENTE']], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'in', ['prod-a-100']], caseSensitive: true)->toArray());
    }

    public function testWhereWithCaseSensitiveMultipleConditions(): void
    {
        $data = [
            ['id' => 1, 'code' => 'ALPHA', 'category' => 'TOOLS'],
            ['id' => 2, 'code' => 'alpha', 'category' => 'tools'],
        ];

        $result = LinqPHP::from($data)
            ->where([
                ['code', '=', 'ALPHA'],
                ['category', '=', 'TOOLS'],
            ], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }
}
