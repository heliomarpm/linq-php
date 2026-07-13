<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class AggregateBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Agregations (Multiple)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(
        ['status'],
        [
          'count' => ['id'],
          'sum' => ['value'],
          'max' => ['value'],
          'min' => ['value'],
          'avg' => ['value'],
        ]
      )
      ->toArray();
  }
}
