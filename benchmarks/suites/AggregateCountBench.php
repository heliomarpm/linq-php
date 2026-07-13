<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateCountBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (count)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'count' => ['id']
        ]
      )
      ->toArray();
  }
}
