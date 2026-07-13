<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class JoinFullBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Joins (FULL)';
  }

  public function run(array $data): void
  {
    [$sales, $products] = $data;
    LinqPHP::from($sales)
      ->join($products, "FULL", ['product_id' => 'id'])
      ->toArray();
  }
}
