<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class JoinInnerBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Joins (INNER)';
  }

  public function run(array $data): void
  {
    [$sales, $products] = $data;
    LinqPHP::from($sales)
      ->join($products, "INNER", ['product_id'=> 'id'])
      ->toArray();
  }
}
