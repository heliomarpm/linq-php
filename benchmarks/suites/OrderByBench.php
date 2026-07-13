<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class OrderbyBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'OrderBy (desc)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->orderBy(['value' => 'desc'])
      ->toArray();
  }
}
