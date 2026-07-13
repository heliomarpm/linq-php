<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateMaxBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (max)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'max' => ['value'],
        ]
      )
      ->toArray();
  }
}
