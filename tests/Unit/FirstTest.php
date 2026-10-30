<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

final class FirstTest extends TestCase
{
    private array $users = [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Bob', 'role' => 'editor'],
        ['id' => 3, 'name' => 'Carol', 'role' => 'editor'],
    ];

    public function testFirstReturnsFirstItemWithoutPredicate(): void
    {
        $this->assertSame($this->users[0], LinqPHP::from($this->users)->first());
    }

    public function testFirstReturnsFirstMatchingItemWithoutChangingCollection(): void
    {
        $linq = LinqPHP::from($this->users);

        $result = $linq->first(static fn(array $user): bool => $user['role'] === 'editor');

        $this->assertSame($this->users[1], $result);
        $this->assertSame($this->users, $linq->toArray());
    }

    public function testFirstStopsAtFirstMatchingItem(): void
    {
        $predicateCalls = 0;

        $result = LinqPHP::from($this->users)->first(
            static function (array $user) use (&$predicateCalls): bool {
                $predicateCalls++;

                return $user['role'] === 'editor';
            }
        );

        $this->assertSame($this->users[1], $result);
        $this->assertSame(2, $predicateCalls);
    }

    public function testFirstOnEmptyCollectionThrowsUnderflowException(): void
    {
        $this->expectException(UnderflowException::class);

        LinqPHP::from([])->first();
    }

    public function testFirstWithoutMatchingItemThrowsUnderflowException(): void
    {
        $this->expectException(UnderflowException::class);

        LinqPHP::from($this->users)->first(
            static fn(array $user): bool => $user['role'] === 'owner'
        );
    }
}
