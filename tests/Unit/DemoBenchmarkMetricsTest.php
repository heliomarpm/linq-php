<?php

declare(strict_types=1);

namespace Tests\Unit;

use HeliomarPM\LinqPHP\Demo\BenchmarkMetrics;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../demo/BenchmarkMetrics.php';

final class DemoBenchmarkMetricsTest extends TestCase
{
    public function testReportsPositiveOverheadWhenLinqPhpIsSlower(): void
    {
        $metrics = BenchmarkMetrics::calculate(120_000_000, 10_000_000, 1_000);

        $this->assertSame(120.0, $metrics['linqTotalMs']);
        $this->assertSame(10.0, $metrics['nativeTotalMs']);
        $this->assertSame(110.0, $metrics['deltaUsPerOp']);
        $this->assertSame(110.0, $metrics['deltaTotalMs']);
        $this->assertSame(12.0, $metrics['ratio']);
    }

    public function testReportsNegativeOverheadWhenLinqPhpIsFaster(): void
    {
        $metrics = BenchmarkMetrics::calculate(5_000_000, 10_000_000, 1_000);

        $this->assertSame(-5.0, $metrics['deltaUsPerOp']);
        $this->assertSame(-5.0, $metrics['deltaTotalMs']);
        $this->assertSame(0.5, $metrics['ratio']);
    }

    public function testReportsMeasuredImpactAbovePointZeroFiveMillisecondsPerOperation(): void
    {
        $metrics = BenchmarkMetrics::calculate(130_000_000, 10_000_000, 1_000);

        $this->assertSame(120.0, $metrics['deltaUsPerOp']);
        $this->assertSame(120.0, $metrics['deltaTotalMs']);
        $this->assertGreaterThan(50.0, $metrics['deltaUsPerOp']);
    }

    public function testRatioIsUnavailableWhenNativeDurationIsZero(): void
    {
        $metrics = BenchmarkMetrics::calculate(1_000, 0, 100);

        $this->assertNull($metrics['ratio']);
    }

    public function testRejectsNonPositiveIterationCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BenchmarkMetrics::calculate(1_000, 1_000, 0);
    }
}
