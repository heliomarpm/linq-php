<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class GroupBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'GroupBy simples';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->groupBy(['status'])
      ->toArray();
  }
}
