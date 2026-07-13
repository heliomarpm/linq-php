<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateSumBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (sum)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'sum' => ['value']
        ]
      )
      ->toArray();
  }
}
