<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateAvgBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (avg)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'count' => ['id'],
          'avg' => ['value'],
        ]
      )
      ->toArray();
  }
}
