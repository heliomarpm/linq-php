<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

class JoinRightBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Joins (RIGHT)';
  }

  public function run(array $data): void
  {
    [$sales, $products] = $data;
    LinqPHP::from($sales)
      ->join($products, JoinType::RIGHT, ['product_id'=> 'id'])
      ->toArray();

  }
}
