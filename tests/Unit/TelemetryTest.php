<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class TelemetryTest extends TestCase
{
    public function testToArrayReturnsFluentResults(): void
    {
        $result = LinqPHP::from([
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
        ])
            ->where(['active', '=', true])
            ->select(['id'])
            ->toArray();

        $this->assertSame([['id' => 1]], $result);
    }

    public function testToObjectCalculatesTelemetryAtTerminalCall(): void
    {
        $result = LinqPHP::from([
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
        ])
            ->where(['active', '=', true])
            ->toObject();

        $this->assertSame(1, $result->count);
        $this->assertSame([['id' => 1, 'active' => true]], $result->data);
        $this->assertSame($result->data, $result->rows);
        $this->assertMatchesRegularExpression('/^\d+\.\d{6}$/', $result->elapsedTime);
        $this->assertMatchesRegularExpression('/^\s*\d+\.\d{2} MB$/', $result->memoryUsed);
    }
}
