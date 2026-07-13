<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class JoinLeftBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Joins (LEFT)';
  }

  public function run(array $data): void
  {
    [$sales, $products] = $data;
    LinqPHP::from($sales)
      ->join($products, "LEFT", ['product_id'=> 'id'])
      ->toArray();

  }
}
