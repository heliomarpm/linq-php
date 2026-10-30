<?php

declare(strict_types=1);

namespace HeliomarPM\LinqPHP\Demo;

use InvalidArgumentException;

final class BenchmarkMetrics
{
    /**
     * @return array{
     *     linqTotalMs: float,
     *     nativeTotalMs: float,
     *     linqUsPerOp: float,
     *     nativeUsPerOp: float,
     *     deltaUsPerOp: float,
     *     deltaTotalMs: float,
     *     ratio: float|null
     * }
     */
    public static function calculate(int $linqNs, int $nativeNs, int $iterations): array
    {
        if ($linqNs < 0 || $nativeNs < 0) {
            throw new InvalidArgumentException('Benchmark durations cannot be negative.');
        }

        if ($iterations <= 0) {
            throw new InvalidArgumentException('Benchmark iterations must be greater than zero.');
        }

        $deltaNs = $linqNs - $nativeNs;

        return [
            'linqTotalMs' => round($linqNs / 1_000_000, 3),
            'nativeTotalMs' => round($nativeNs / 1_000_000, 3),
            'linqUsPerOp' => round(($linqNs / $iterations) / 1_000, 2),
            'nativeUsPerOp' => round(($nativeNs / $iterations) / 1_000, 2),
            'deltaUsPerOp' => round(($deltaNs / $iterations) / 1_000, 2),
            'deltaTotalMs' => round($deltaNs / 1_000_000, 3),
            'ratio' => $nativeNs > 0 ? round($linqNs / $nativeNs, 2) : null,
        ];
    }
}
