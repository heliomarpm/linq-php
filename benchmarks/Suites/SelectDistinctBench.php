<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class SelectDistinctBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Select->Distinct (product_id, status)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->select(['product_id', 'status'])
      ->distinct()
      ->toArray();
  }
}
