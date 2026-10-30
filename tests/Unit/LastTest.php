<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

final class LastTest extends TestCase
{
    private array $users = [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Bob', 'role' => 'editor'],
        ['id' => 3, 'name' => 'Carol', 'role' => 'editor'],
        ['id' => 4, 'name' => 'David', 'role' => 'user'],
    ];

    public function testLastReturnsLastItemWithoutPredicate(): void
    {
        $this->assertSame($this->users[3], LinqPHP::from($this->users)->last());
    }

    public function testLastReturnsLastMatchingItemWithoutChangingCollection(): void
    {
        $linq = LinqPHP::from($this->users);

        $result = $linq->last(static fn(array $user): bool => $user['role'] === 'editor');

        $this->assertSame($this->users[2], $result);
        $this->assertSame($this->users, $linq->toArray());
    }

    public function testLastOnEmptyCollectionThrowsUnderflowException(): void
    {
        $this->expectException(UnderflowException::class);

        LinqPHP::from([])->last();
    }

    public function testLastWithoutMatchingItemThrowsUnderflowException(): void
    {
        $this->expectException(UnderflowException::class);

        LinqPHP::from($this->users)->last(
            static fn(array $user): bool => $user['role'] === 'owner'
        );
    }

    public function testLastOrDefaultReturnsLastItemWithoutPredicate(): void
    {
        $this->assertSame($this->users[3], LinqPHP::from($this->users)->lastOrDefault());
    }

    public function testLastOrDefaultOnEmptyCollectionReturnsNullByDefault(): void
    {
        $this->assertNull(LinqPHP::from([])->lastOrDefault());
    }

    public function testLastOrDefaultOnEmptyCollectionReturnsCustomDefault(): void
    {
        $default = ['id' => 0, 'name' => 'Guest'];

        $this->assertSame($default, LinqPHP::from([])->lastOrDefault(default: $default));
    }

    public function testLastOrDefaultWithPredicateReturnsLastMatch(): void
    {
        $result = LinqPHP::from($this->users)->lastOrDefault(
            static fn(array $user): bool => $user['role'] === 'editor'
        );

        $this->assertSame($this->users[2], $result);
    }

    public function testLastOrDefaultWithNoMatchReturnsCustomDefault(): void
    {
        $default = ['id' => -1, 'name' => 'Fallback'];

        $result = LinqPHP::from($this->users)->lastOrDefault(
            static fn(array $user): bool => $user['role'] === 'owner',
            default: $default
        );

        $this->assertSame($default, $result);
    }
}
