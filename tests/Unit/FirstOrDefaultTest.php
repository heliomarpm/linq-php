<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class FirstOrDefaultTest extends TestCase
{
    private array $users = [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin',  'active' => true],
        ['id' => 2, 'name' => 'Bob',   'role' => 'editor', 'active' => false],
        ['id' => 3, 'name' => 'Carol', 'role' => 'editor', 'active' => true],
        ['id' => 4, 'name' => 'David', 'role' => 'user',   'active' => true],
    ];

    public function testFirstOrDefaultOnEmptyCollectionReturnsNullByDefault(): void
    {
        $result = LinqPHP::from([])->firstOrDefault();
        $this->assertNull($result);
    }

    public function testFirstOrDefaultOnEmptyCollectionReturnsCustomDefault(): void
    {
        $default = ['id' => 0, 'name' => 'Guest'];
        $result = LinqPHP::from([])->firstOrDefault(default: $default);
        $this->assertSame($default, $result);
    }

    public function testFirstOrDefaultWithoutPredicateReturnsFirstElement(): void
    {
        $result = LinqPHP::from($this->users)->firstOrDefault();
        $this->assertSame($this->users[0], $result);
    }

    public function testFirstOrDefaultWithPredicateMatchingFirstElement(): void
    {
        $result = LinqPHP::from($this->users)->firstOrDefault(fn($u) => $u['name'] === 'Alice');
        $this->assertSame($this->users[0], $result);
    }

    public function testFirstOrDefaultWithPredicateMatchingMiddleElement(): void
    {
        $result = LinqPHP::from($this->users)->firstOrDefault(fn($u) => $u['name'] === 'Carol');
        $this->assertSame($this->users[2], $result);
    }

    public function testFirstOrDefaultWithPredicateNoMatchReturnsNullByDefault(): void
    {
        $result = LinqPHP::from($this->users)->firstOrDefault(fn($u) => $u['name'] === 'NonExistent');
        $this->assertNull($result);
    }

    public function testFirstOrDefaultWithPredicateNoMatchReturnsCustomDefault(): void
    {
        $fallback = ['id' => -1, 'name' => 'Fallback'];
        $result = LinqPHP::from($this->users)->firstOrDefault(
            fn($u) => $u['id'] === 999,
            default: $fallback
        );
        $this->assertSame($fallback, $result);
    }

    public function testFirstOrDefaultShortCircuitsOnFirstMatch(): void
    {
        $callCount = 0;

        $result = LinqPHP::from($this->users)->firstOrDefault(function ($u) use (&$callCount) {
            $callCount++;
            return $u['role'] === 'editor'; // Deverá dar match no Bob (segundo item)
        });

        $this->assertSame($this->users[1], $result);
        $this->assertSame(2, $callCount, 'O predicado deve parar de avaliar assim que encontrar o primeiro correspondente.');
    }

    public function testFirstOrDefaultChainedWithFluentMethods(): void
    {
        $result = LinqPHP::from($this->users)
            ->where(['active', '=', true])
            ->orderByKey('id', descending: true)
            ->select(['id', 'name'])
            ->firstOrDefault();

        // O ativo com maior id é David (id: 4)
        $this->assertSame(['id' => 4, 'name' => 'David'], $result);
    }

    public function testFirstOrDefaultReturnsFirstItemWhenPredicateMatchesMultiple(): void
    {
        // Existem dois editores: Bob (id: 2) e Carol (id: 3). Deve retornar o primeiro encontrado (Bob).
        $result = LinqPHP::from($this->users)->firstOrDefault(fn($u) => $u['role'] === 'editor');
        $this->assertSame($this->users[1], $result);
    }
}

