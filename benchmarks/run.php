<?php

// require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use Benchmarks\BenchmarkRunner;
use Benchmarks\Suites;

$runner = new BenchmarkRunner();

$suites = [
  new Suites\SelectBench(),
  new Suites\SelectDistinctBench(),
  new Suites\WhereBench(),
  new Suites\OrderByBench(),
  new Suites\OrderByKeyBench(),
  new Suites\GroupBench(),
  new Suites\AggregateCountBench(),
  new Suites\AggregateSumBench(),
  new Suites\AggregateMaxBench(),
  new Suites\AggregateMinBench(),
  new Suites\AggregateAvgBench(),
  new Suites\AggregateBench(),
  new Suites\JoinInnerBench(),
  new Suites\JoinLeftBench(),
  new Suites\JoinRightBench(),
  new Suites\JoinFullBench(),
  new Suites\UnionAllBench(),
  new Suites\CombinedBench(),
];

$sizes = [500, 5_000, 10_000, 25_000, 50_000, 100_000];

$runner->run($suites, $sizes);
