<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

class JoinTest extends TestCase
{
  private function generateLeftData(int $size): array
  {
    $data = [];

    for ($i = 1; $i <= $size; $i++) {
      $data[] = [
        'id' => $i,
        'user_name' => 'User ' . $i,
        'group_id' => $i % 10
      ];
    }

    return $data;
  }

  private function generateRightData(int $size): array
  {
    $data = [];

    for ($i = 1; $i <= $size; $i++) {
      $data[] = [
        'id' => $i,
        'group_id' => $i % 10,
        'score' => rand(0, 100)
      ];
    }

    // adiciona registros sem match no LEFT
    for ($i = $size + 1; $i <= $size + 20; $i++) {
      $data[] = [
        'id' => $i,
        'group_id' => $i % 10,
        'score' => rand(0, 100)
      ];
    }

    return $data;
  }

  // ==========================================================
  // INNER JOIN
  // ==========================================================
  public function testInnerJoinWithLargeDataset(): void
  {
    $left = $this->generateLeftData(200);
    $right = $this->generateRightData(200);

    $result = LinqPHP::from($left)
      ->join($right, JoinType::INNER, ['id', 'group_id'])
      ->toArray();

    // apenas ids existentes em ambos
    $this->assertCount(200, $result);

    foreach ($result as $row) {
      $this->assertNotNull($row['user_name']);
      $this->assertNotNull($row['score']);
    }
  }

  // ==========================================================
  // LEFT JOIN
  // ==========================================================
  public function testLeftJoinPreservesAllLeftRows(): void
  {
    $left = $this->generateLeftData(200);
    $right = $this->generateRightData(100); // metade não casa

    $result = LinqPHP::from($left)
      ->join($right, JoinType::LEFT, ['id', 'group_id'])
      ->toArray();

    // LEFT sempre preserva todos
    $this->assertCount(200, $result);

    $nullCount = 0;

    foreach ($result as $row) {
      if ($row['score'] === null) {
        $nullCount++;
      }
    }

    $this->assertGreaterThan(0, $nullCount);
  }

  // ==========================================================
  // RIGHT JOIN
  // ==========================================================
  public function testRightJoinPreservesAllRightRows(): void
  {
    $left = $this->generateLeftData(100);
    $right = $this->generateRightData(200);

    $result = LinqPHP::from($left)
      ->join($right, JoinType::RIGHT, ['id', 'group_id'])
      ->toArray();

    // RIGHT preserva todos do lado direito
    $this->assertCount(220, $result);

    $rightOnlyRows = array_filter(
      $result,
      fn($r) => $r['user_name'] === null
    );

    $this->assertGreaterThan(0, count($rightOnlyRows));
  }

  // ==========================================================
  // FULL JOIN
  // ==========================================================
  public function testFullJoinIncludesAllRowsFromBothSides(): void
  {
    $left = $this->generateLeftData(150);
    $right = $this->generateRightData(200);

    $result = LinqPHP::from($left)
      ->join($right, JoinType::FULL, ['id', 'group_id'])
      ->toArray();

    // FULL = LEFT + RIGHT únicos
    $this->assertGreaterThanOrEqual(200, count($result));

    $hasLeftOnly = false;
    $hasRightOnly = false;

    foreach ($result as $row) {
      if ($row['user_name'] === null && $row['score'] !== null) {
        $hasRightOnly = true;
      }

      if ($row['user_name'] !== null && $row['score'] === null) {
        $hasLeftOnly = true;
      }
    }

    $this->assertFalse($hasLeftOnly);
    $this->assertTrue($hasRightOnly);
  }

  public function testInnerJoin()
  {
    $a = [
      ['id' => 1, 'course_id' => 10],
      ['id' => 2, 'course_id' => 20],
    ];

    $b = [
      ['course_id' => 10, 'name' => 'Math'],
      ['course_id' => 30, 'name' => 'Physics'],
    ];

    $expected = [
      ['id' => 1, 'course_id' => 10, 'name' => 'Math']
    ];

    $result = LinqPHP::from($a)
      ->join($b, JoinType::INNER, ['course_id'])
      ->toArray();

    $this->assertCount(1, $result);
    $this->assertEquals('Math', $result[0]['name']);
    $this->assertEquals($expected, $result);
  }

  public function testLeftJoin()
  {
    $a = [
      ['id' => 1, 'course_id' => 10, 'name' => 'Math'],
    ];

    $b = [];

    $expected = $a;

    $result = LinqPHP::from($a)
      ->join($b, JoinType::LEFT, ['course_id'])
      ->toArray();

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('id', $result[0]);
    $this->assertEquals($expected, $result);
  }

  public function testRightJoin()
  {
    $a = [];
    $b = [
      ['course_id' => 10, 'name' => 'Math'],
      ['course_id' => 30, 'name' => 'Physics'],
    ];

    $expected = $b;

    $result = LinqPHP::from($a)
      ->join($b, JoinType::RIGHT, ['course_id'])
      ->toArray();

    $this->assertCount(2, $result);
    $this->assertArrayHasKey('course_id', $result[0]);
    $this->assertContains(['course_id' => 10, 'name' => 'Math'], $result);
    $this->assertContains(['course_id' => 30, 'name' => 'Physics'], $result);

    $this->assertEquals($expected, $result);
  }

  public function testJoinWithInnerJoin()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1],
    ];

    $array2 = [
      ['id' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'age' => 25, 'course_id' => 2],
      ['id' => 3, 'age' => 30, 'course_id' => 1],
    ];

    $expectedResult = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1, 'age' => 25, 'course_id' => 1]
    ];

    $instance = new LinqPHP($data);
    $instance->join($array2, JoinType::INNER, ['id', 'id_course' => 'course_id']);
    $result = $instance->toArray();

    $this->assertEquals($expectedResult, $result);
  }

  public function testJoinWithLeftJoin()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1],
    ];

    $array2 = [
      ['id' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'age' => 25, 'course_id' => 2],
      ['id' => 3, 'age' => 30, 'course_id' => 1],
    ];

    $expectedResult = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1, 'age' => null, 'course_id' => null],
    ];

    $instance = new LinqPHP($data);
    $instance->join($array2, JoinType::LEFT, ['id', 'id_course' => 'course_id']);
    $result = $instance->toArray();

    $this->assertEquals($expectedResult, $result);
  }

  public function testJoinWithRightJoin()
  {
    $a = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1],
    ];

    $b = [
      ['id' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'age' => 25, 'course_id' => 2],
      ['id' => 3, 'age' => 30, 'course_id' => 1],
    ];

    $expectedResult = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'name' => null, 'id_course' => null, 'age' => 25, 'course_id' => 2],
      ['id' => 3, 'name' => null, 'id_course' => null, 'age' => 30, 'course_id' => 1],
    ];

    $instance = new LinqPHP($a);
    $instance->join($b, JoinType::RIGHT, ['id', 'id_course' => 'course_id']);
    $result = $instance->toArray();

    $this->assertEquals($expectedResult, $result);
  }

  public function testJoinWithFullJoin()
  {
    $data = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1],
    ];

    $array2 = [
      ['id' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'age' => 25, 'course_id' => 2],
      ['id' => 3, 'age' => 30, 'course_id' => 1],
    ];

    $expectedResult = [
      ['id' => 1, 'name' => 'John', 'id_course' => 1, 'age' => 25, 'course_id' => 1],
      ['id' => 1, 'name' => null, 'id_course' => null, 'age' => 25, 'course_id' => 2],
      ['id' => 2, 'name' => 'Jane', 'id_course' => 1, 'age' => null, 'course_id' => null],
      ['id' => 3, 'name' => null, 'id_course' => null, 'age' => 30, 'course_id' => 1],
    ];

    $instance = new LinqPHP($data);
    $instance->join($array2, JoinType::FULL, ['id', 'id_course' => 'course_id'])
      ->orderBy(['id' => 'asc', 'course_id' => 'asc']);
    $result = $instance->toArray();

    $this->assertEquals($expectedResult, $result);
  }
}
