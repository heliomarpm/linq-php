<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class DistinctTest extends TestCase
{
    private array $data = [
        ['id' => 1, 'cpf' => '111', 'name' => 'Ana', 'city' => 'SP'],
        ['id' => 2, 'cpf' => '222', 'name' => 'Bob', 'city' => 'RJ'],
        ['id' => 3, 'cpf' => '111', 'name' => 'Ana Clone', 'city' => 'MG'],
        ['id' => 4, 'cpf' => '333', 'name' => 'Bia', 'city' => 'SP'],
    ];

    public function testDistinct(): void
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
        $result = LinqPHP::from($data)->distinct()->toArray();
        $this->assertEquals($expected, $result);
    }

    public function testDistinctByStringColumn(): void
    {
        $result = LinqPHP::from($this->data)->distinct('cpf')->toArray();

        $this->assertCount(3, $result);
        $this->assertSame([1, 2, 4], array_column($result, 'id'));
    }

    public function testDistinctByStringColumnPreservesFirstOccurrence(): void
    {
        $result = LinqPHP::from($this->data)->distinct('city')->toArray();

        $this->assertCount(3, $result);
        // SP first occurrence is id=1, RJ is id=2, MG is id=3
        $this->assertSame([1, 2, 3], array_column($result, 'id'));
    }

    public function testDistinctByCallable(): void
    {
        $result = LinqPHP::from($this->data)
            ->distinct(fn($item) => strtolower($item['name'][0]))
            ->toArray();

        // 'Ana'=A, 'Bob'=B, 'Ana Clone'=A (dup), 'Bia'=B (dup)
        $this->assertCount(2, $result);
        $this->assertSame([1, 2], array_column($result, 'id'));
    }

    public function testDistinctByCallableWithComplexKey(): void
    {
        $data = [
            ['id' => 1, 'categoria' => 'frutas', 'tipo' => 'tropical'],
            ['id' => 2, 'categoria' => 'frutas', 'tipo' => 'tropical'],
            ['id' => 3, 'categoria' => 'frutas', 'tipo' => 'temperado'],
            ['id' => 4, 'categoria' => 'legumes', 'tipo' => 'tropical'],
        ];

        $result = LinqPHP::from($data)
            ->distinct(fn($item) => $item['categoria'] . '|' . $item['tipo'])
            ->toArray();

        $this->assertCount(3, $result);
        $this->assertSame([1, 3, 4], array_column($result, 'id'));
    }

    public function testDistinctWithNullColumnValueGroupedAsEmptyString(): void
    {
        $data = [
            ['id' => 1, 'tag' => null],
            ['id' => 2, 'tag' => null],
            ['id' => 3, 'tag' => 'php'],
        ];

        $result = LinqPHP::from($data)->distinct('tag')->toArray();

        // null é convertido para '' via (string)null, então id=1 e id=2 são deduplicados
        $this->assertCount(2, $result);
        $this->assertSame([1, 3], array_column($result, 'id'));
    }

    public function testDistinctLegacyNullPreservesFullRecordDeduplication(): void
    {
        $data = [
            ['id' => 1, 'val' => 0],
            ['id' => 2, 'val' => '0'],
            ['id' => 1, 'val' => 0],
        ];

        $result = LinqPHP::from($data)->distinct()->toArray();

        // Todos os 3 campos participam da chave — id=1,val=0 != id=2,val='0'
        $this->assertCount(2, $result);
        $this->assertSame([1, 2], array_column($result, 'id'));
    }
}
