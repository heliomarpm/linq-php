<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class UnionAllBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Union All';
  }

  public function run(array $data): void
  {
    [$sales, $products] = $data;
    LinqPHP::from($sales)->unionAll($products)->toArray();
  }
}
