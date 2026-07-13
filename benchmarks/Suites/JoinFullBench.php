<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

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
      ->join($products, JoinType::FULL, ['product_id' => 'id'])
      ->toArray();
  }
}
