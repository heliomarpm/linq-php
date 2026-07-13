<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class SelectBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Select (id, status)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->select(['id', 'status'])
      ->toArray();
  }
}
