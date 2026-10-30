<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

final class PluckTest extends TestCase
{
    private array $products = [
        ['id' => 101, 'name' => 'PLA', 'price' => 90.0],
        ['id' => 102, 'name' => 'PETG', 'price' => 95.5],
        ['id' => 103, 'name' => 'Resina', 'price' => 180.0],
    ];

    public function testPluckReturnsColumnValuesAsAList(): void
    {
        $result = LinqPHP::from($this->products)->pluck('name');

        $this->assertSame(['PLA', 'PETG', 'Resina'], $result);
    }

    public function testPluckCanIndexValuesByAnotherColumn(): void
    {
        $result = LinqPHP::from($this->products)->pluck('name', 'id');

        $this->assertSame([
            101 => 'PLA',
            102 => 'PETG',
            103 => 'Resina',
        ], $result);
    }

    public function testPluckDoesNotChangeTheCollection(): void
    {
        $linq = LinqPHP::from($this->products);

        $linq->pluck('price');

        $this->assertSame($this->products, $linq->toArray());
    }

    public function testPluckReturnsAnEmptyListForAnEmptyCollection(): void
    {
        $this->assertSame([], LinqPHP::from([])->pluck('name'));
    }
}
