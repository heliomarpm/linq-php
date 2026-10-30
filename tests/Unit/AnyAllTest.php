<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

final class AnyAllTest extends TestCase
{
    private array $users = [
        ['id' => 1, 'active' => true],
        ['id' => 2, 'active' => false],
        ['id' => 3, 'active' => true],
    ];

    public function testAnyWithoutPredicateIndicatesWhetherCollectionIsNotEmpty(): void
    {
        $this->assertTrue(LinqPHP::from($this->users)->any());
        $this->assertFalse(LinqPHP::from([])->any());
    }

    public function testAnyWithPredicateReturnsTrueForMatch(): void
    {
        $result = LinqPHP::from($this->users)->any(
            static fn(array $user): bool => $user['id'] === 2
        );

        $this->assertTrue($result);
    }

    public function testAnyWithPredicateReturnsFalseWithoutMatch(): void
    {
        $result = LinqPHP::from($this->users)->any(
            static fn(array $user): bool => $user['id'] === 4
        );

        $this->assertFalse($result);
    }

    public function testAnyStopsAtFirstMatch(): void
    {
        $predicateCalls = 0;

        $result = LinqPHP::from($this->users)->any(
            static function (array $user) use (&$predicateCalls): bool {
                $predicateCalls++;

                return $user['id'] === 2;
            }
        );

        $this->assertTrue($result);
        $this->assertSame(2, $predicateCalls);
    }

    public function testAllReturnsTrueWhenEveryItemMatches(): void
    {
        $result = LinqPHP::from($this->users)->all(
            static fn(array $user): bool => $user['id'] > 0
        );

        $this->assertTrue($result);
    }

    public function testAllReturnsFalseWhenAnyItemDoesNotMatch(): void
    {
        $result = LinqPHP::from($this->users)->all(
            static fn(array $user): bool => $user['active']
        );

        $this->assertFalse($result);
    }

    public function testAllStopsAtFirstNonMatchingItem(): void
    {
        $predicateCalls = 0;

        $result = LinqPHP::from($this->users)->all(
            static function (array $user) use (&$predicateCalls): bool {
                $predicateCalls++;

                return $user['active'];
            }
        );

        $this->assertFalse($result);
        $this->assertSame(2, $predicateCalls);
    }

    public function testAllReturnsTrueForEmptyCollection(): void
    {
        $this->assertTrue(LinqPHP::from([])->all(static fn(array $user): bool => $user['active']));
    }
}
