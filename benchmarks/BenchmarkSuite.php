<?php

namespace Benchmarks;

abstract class BenchmarkSuite
{
    abstract public function name(): string;

    abstract public function run(array $data): void;
}
