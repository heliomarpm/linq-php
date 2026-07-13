<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateMinBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (min)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'min' => ['value']
        ]
      )
      ->toArray();
  }
}
