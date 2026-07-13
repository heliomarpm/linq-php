<?php

namespace Benchmarks\Suites;

use Benchmarks\BenchmarkSuite;
use HeliomarPM\LinqPHP\LinqPHP;

class CombinedBench extends BenchmarkSuite
{
  public function name(): string
  {
    return 'Combinação  (where->select->groupBy->orderBy)';
  }

  public function run(array $data): void
  {
    [$sales] = $data;
    LinqPHP::from($sales)
      ->where(['value', '>', 40])
      ->select(['id', 'status', 'value'])
      ->groupBy(
        ['status'],
        [
          'sum' => ['value'],
          'avg' => ['value']
        ]
      )
      ->orderByKey('status')
      ->toArray();
  }
}
