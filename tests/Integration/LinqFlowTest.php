<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

class LinqFlowTest extends TestCase
{
    public function testCompleteFlow()
    {
        $users = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $orders = [
            ['user_id' => 1, 'total' => 100],
            ['user_id' => 1, 'total' => 50],
        ];

        $result = LinqPHP::from($users)
            ->join($orders, JoinType::LEFT, ['id' => 'user_id'])
            ->where(fn($x) => $x['total'] !== null)
            ->groupBy(['id'], ['sum' => ['total']])
            ->toArray();

        $this->assertEquals(150, $result[0]['total']);
    }
}
