<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class ScalarAggregationsTest extends TestCase
{
    private array $products = [
        ['id' => 1, 'name' => 'Notebook', 'category' => 'tech', 'price' => 3500.00, 'qty' => 5],
        ['id' => 2, 'name' => 'Mouse',    'category' => 'tech', 'price' => 150.50,  'qty' => 20],
        ['id' => 3, 'name' => 'Cadeira',  'category' => 'home', 'price' => 1200.00, 'qty' => 2],
        ['id' => 4, 'name' => 'Teclado',  'category' => 'tech', 'price' => 350.00,  'qty' => 10],
        ['id' => 5, 'name' => 'Luminaria','category' => 'home', 'price' => 90.00,   'qty' => 0],
    ];

    public function testCountWithoutPredicateReturnsTotalItems(): void
    {
        $count = LinqPHP::from($this->products)->count();
        $this->assertSame(5, $count);
    }

    public function testCountWithPredicateFiltersMatchingItems(): void
    {
        $techCount = LinqPHP::from($this->products)->count(fn($p) => $p['category'] === 'tech');
        $this->assertSame(3, $techCount);

        $outOfStock = LinqPHP::from($this->products)->count(fn($p) => $p['qty'] === 0);
        $this->assertSame(1, $outOfStock);
    }

    public function testCountOnEmptyCollection(): void
    {
        $this->assertSame(0, LinqPHP::from([])->count());
        $this->assertSame(0, LinqPHP::from([])->count(fn($x) => true));
    }

    public function testSumByFieldName(): void
    {
        $totalQty = LinqPHP::from($this->products)->sum('qty');
        $this->assertSame(37, $totalQty);

        $totalPrice = LinqPHP::from($this->products)->sum('price');
        $this->assertEquals(5290.50, $totalPrice);
    }

    public function testSumWithCallableProjection(): void
    {
        // Valor total em estoque = sum(qty * price)
        $totalStockValue = LinqPHP::from($this->products)->sum(fn($p) => $p['qty'] * $p['price']);
        // 5*3500 (17500) + 20*150.5 (3010) + 2*1200 (2400) + 10*350 (3500) + 0*90 (0) = 26410.0
        $this->assertEquals(26410.0, $totalStockValue);
    }

    public function testSumOnEmptyCollectionReturnsZero(): void
    {
        $this->assertSame(0, LinqPHP::from([])->sum('price'));
        $this->assertSame(0, LinqPHP::from([])->sum(fn($x) => $x['price']));
    }

    public function testSumWithMissingOrNonNumericValues(): void
    {
        $data = [
            ['val' => 10],
            ['val' => null],
            ['val' => 'invalid'],
            ['val' => 25],
        ];

        $this->assertEquals(35, LinqPHP::from($data)->sum('val'));
    }

    public function testAvgByFieldName(): void
    {
        $avgPrice = LinqPHP::from($this->products)->avg('price');
        // 5290.50 / 5 = 1058.10
        $this->assertEquals(1058.10, $avgPrice);
    }

    public function testAvgWithCallable(): void
    {
        $data = [
            ['score1' => 8, 'score2' => 10],
            ['score1' => 6, 'score2' => 8],
        ];

        $avgMean = LinqPHP::from($data)->avg(fn($row) => ($row['score1'] + $row['score2']) / 2);
        // (9 + 7) / 2 = 8.0
        $this->assertEquals(8.0, $avgMean);
    }

    public function testAvgOnEmptyCollectionReturnsNull(): void
    {
        $this->assertNull(LinqPHP::from([])->avg('price'));
    }

    public function testMinByFieldName(): void
    {
        $minPrice = LinqPHP::from($this->products)->min('price');
        $this->assertEquals(90.00, $minPrice);

        $minQty = LinqPHP::from($this->products)->min('qty');
        $this->assertSame(0, $minQty);
    }

    public function testMinWithCallable(): void
    {
        $minStockValue = LinqPHP::from($this->products)->min(fn($p) => $p['qty'] * $p['price']);
        $this->assertEquals(0.0, $minStockValue);
    }

    public function testMinOnEmptyCollectionReturnsNull(): void
    {
        $this->assertNull(LinqPHP::from([])->min('price'));
    }

    public function testMaxByFieldName(): void
    {
        $maxPrice = LinqPHP::from($this->products)->max('price');
        $this->assertEquals(3500.00, $maxPrice);

        $maxQty = LinqPHP::from($this->products)->max('qty');
        $this->assertSame(20, $maxQty);
    }

    public function testMaxWithCallable(): void
    {
        $maxStockValue = LinqPHP::from($this->products)->max(fn($p) => $p['qty'] * $p['price']);
        $this->assertEquals(17500.0, $maxStockValue);
    }

    public function testMaxOnEmptyCollectionReturnsNull(): void
    {
        $this->assertNull(LinqPHP::from([])->max('price'));
    }

    public function testChainingWithWhereFilter(): void
    {
        $techTotalPrice = LinqPHP::from($this->products)
            ->where(['category', '=', 'tech'])
            ->sum('price');

        // 3500 + 150.5 + 350 = 4000.50
        $this->assertEquals(4000.50, $techTotalPrice);

        $homeCount = LinqPHP::from($this->products)
            ->where(fn($p) => $p['category'] === 'home')
            ->count();

        $this->assertSame(2, $homeCount);
    }
}
