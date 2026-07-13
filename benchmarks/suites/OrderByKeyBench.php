<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class OrderByKeyBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'OrderByKey (desc)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->orderByKey('value', 'desc')
      ->toArray();
  }
}
