<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class WhereBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Where simples';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->where(['value', '>', 50])
      ->toArray();
  }
}
