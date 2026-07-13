<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

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
      ->join($products, JoinType::INNER, ['product_id'=> 'id'])
      ->toArray();
  }
}
