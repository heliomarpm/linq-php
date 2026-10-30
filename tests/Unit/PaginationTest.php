<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testSkipReturnsItemsAfterOffset(): void
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ];

        $result = LinqPHP::from($data)->skip(2)->toArray();

        $this->assertSame([['id' => 3], ['id' => 4]], $result);
    }

    public function testSkipZeroKeepsAllItems(): void
    {
        $data = [['id' => 1], ['id' => 2]];

        $this->assertSame($data, LinqPHP::from($data)->skip(0)->toArray());
    }

    public function testSkipBeyondItemCountReturnsEmptyList(): void
    {
        $result = LinqPHP::from([['id' => 1], ['id' => 2]])->skip(3)->toArray();

        $this->assertSame([], $result);
    }

    public function testSkipRejectsNegativeOffset(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LinqPHP::from([['id' => 1]])->skip(-1);
    }

    public function testSkipComposesWithTakeForPagination(): void
    {
        $data = array_map(static fn(int $id): array => ['id' => $id], range(1, 10));

        $result = LinqPHP::from($data)->skip(3)->take(4)->toArray();

        $this->assertSame([
            ['id' => 4],
            ['id' => 5],
            ['id' => 6],
            ['id' => 7],
        ], $result);
    }
}
