<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class OrderByTest extends TestCase
{
    private array $data = [
        ['id' => 2, 'name' => 'Carlos', 'age' => 28, 'score' => 90],
        ['id' => 1, 'name' => 'Ana',    'age' => 22, 'score' => 85],
        ['id' => 4, 'name' => 'Bruno',  'age' => 35, 'score' => 85],
        ['id' => 3, 'name' => 'Carlos', 'age' => 20, 'score' => 95],
    ];

    public function testOrderByKeyWithBooleanDescendingTrue(): void
    {
        $result = LinqPHP::from($this->data)->orderByKey('id', true)->toArray();

        $this->assertSame([4, 3, 2, 1], array_column($result, 'id'));
    }

    public function testOrderByKeyWithBooleanDescendingFalse(): void
    {
        $result = LinqPHP::from($this->data)->orderByKey('id', false)->toArray();

        $this->assertSame([1, 2, 3, 4], array_column($result, 'id'));
    }

    public function testOrderByKeyWithNamedArgumentDescending(): void
    {
        $resultDesc = LinqPHP::from($this->data)->orderByKey('age', descending: true)->toArray();
        $this->assertSame([35, 28, 22, 20], array_column($resultDesc, 'age'));

        $resultAsc = LinqPHP::from($this->data)->orderByKey('age', descending: false)->toArray();
        $this->assertSame([20, 22, 28, 35], array_column($resultAsc, 'age'));
    }

    public function testOrderByKeyRetrocompatibilityWithStrings(): void
    {
        $resultAsc = LinqPHP::from($this->data)->orderByKey('id', 'asc')->toArray();
        $this->assertSame([1, 2, 3, 4], array_column($resultAsc, 'id'));

        $resultDesc = LinqPHP::from($this->data)->orderByKey('id', 'desc')->toArray();
        $this->assertSame([4, 3, 2, 1], array_column($resultDesc, 'id'));
    }

    public function testOrderByKeyThrowsExceptionOnNonExistentKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This field does not exist for sorting.');

        LinqPHP::from($this->data)->orderByKey('campo_inexistente', true);
    }

    public function testOrderByKeyOnEmptyData(): void
    {
        $result = LinqPHP::from([])->orderByKey('id', true)->toArray();
        $this->assertSame([], $result);
    }

    public function testOrderByWithIndexedArrayDefaultsToAscending(): void
    {
        $result = LinqPHP::from($this->data)->orderBy(['score', 'age'])->toArray();

        $this->assertSame(85, $result[0]['score']);
        $this->assertSame(22, $result[0]['age']); // 22 vem antes de 35
        $this->assertSame(85, $result[1]['score']);
        $this->assertSame(35, $result[1]['age']);
        $this->assertSame(90, $result[2]['score']);
        $this->assertSame(95, $result[3]['score']);
    }

    public function testOrderByWithAssociativeArrayStringsAscAndDesc(): void
    {
        $result = LinqPHP::from($this->data)
            ->orderBy(['name' => 'asc', 'age' => 'desc'])
            ->toArray();

        $this->assertSame('Ana', $result[0]['name']);
        $this->assertSame('Bruno', $result[1]['name']);
        $this->assertSame('Carlos', $result[2]['name']);
        $this->assertSame(28, $result[2]['age']);
        $this->assertSame('Carlos', $result[3]['name']);
        $this->assertSame(20, $result[3]['age']);
    }

    public function testOrderByWithBooleanValuesInAssociativeArray(): void
    {
        // false = ASC, true = DESC
        $result = LinqPHP::from($this->data)
            ->orderBy(['name' => false, 'age' => true])
            ->toArray();

        $this->assertSame('Ana', $result[0]['name']);
        $this->assertSame('Bruno', $result[1]['name']);
        $this->assertSame('Carlos', $result[2]['name']);
        $this->assertSame(28, $result[2]['age']);
        $this->assertSame('Carlos', $result[3]['name']);
        $this->assertSame(20, $result[3]['age']);
    }

    public function testOrderByOnEmptyData(): void
    {
        $result = LinqPHP::from([])->orderBy(['id'])->toArray();
        $this->assertSame([], $result);
    }

    public function testOrderByThrowsExceptionOnNonExistentKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This field does not exist for sorting.');

        LinqPHP::from($this->data)->orderBy(['id', 'inexistente']);
    }

    public function testOrderByKeyWithCalculatedExtractionClosureAscending(): void
    {
        // Ordena pela expressão calculada: score - age
        // Ana: 85 - 22 = 63
        // Bruno: 85 - 35 = 50
        // Carlos 28: 90 - 28 = 62
        // Carlos 20: 95 - 20 = 75
        // Ordem ASC calculada esperada: Bruno (50), Carlos 28 (62), Ana (63), Carlos 20 (75)
        $result = LinqPHP::from($this->data)
            ->orderByKey(fn(array $x): int => $x['score'] - $x['age'])
            ->toArray();

        $this->assertSame(['Bruno', 'Carlos', 'Ana', 'Carlos'], array_column($result, 'name'));
        $this->assertSame([35, 28, 22, 20], array_column($result, 'age'));
    }

    public function testOrderByKeyWithCalculatedExtractionClosureDescending(): void
    {
        // Ordem DESC da expressão: Carlos 20 (75), Ana (63), Carlos 28 (62), Bruno (50)
        $result = LinqPHP::from($this->data)
            ->orderByKey(fn(array $x): int => $x['score'] - $x['age'], descending: true)
            ->toArray();

        $this->assertSame(['Carlos', 'Ana', 'Carlos', 'Bruno'], array_column($result, 'name'));
        $this->assertSame([20, 22, 28, 35], array_column($result, 'age'));
    }

    public function testOrderByKeyWithComparatorClosureUsortStyle(): void
    {
        // Comparador de 2 argumentos estilo usort
        $result = LinqPHP::from($this->data)
            ->orderByKey(fn(array $a, array $b): int => strcmp($a['name'], $b['name']))
            ->toArray();

        $names = array_column($result, 'name');
        $this->assertSame(['Ana', 'Bruno', 'Carlos', 'Carlos'], $names);

        // Com descending: true
        $resultDesc = LinqPHP::from($this->data)
            ->orderByKey(fn(array $a, array $b): int => strcmp($a['name'], $b['name']), descending: true)
            ->toArray();

        $namesDesc = array_column($resultDesc, 'name');
        $this->assertSame(['Carlos', 'Carlos', 'Bruno', 'Ana'], $namesDesc);
    }

    public function testOrderByKeyWithClosureOnEmptyData(): void
    {
        $result = LinqPHP::from([])
            ->orderByKey(fn(array $x): mixed => $x['id'])
            ->toArray();

        $this->assertSame([], $result);
    }

    public function testOrderByDirectCallableSingleParam(): void
    {
        $result = LinqPHP::from($this->data)
            ->orderBy(fn(array $x): int => $x['age'])
            ->toArray();

        $this->assertSame([20, 22, 28, 35], array_column($result, 'age'));
    }

    public function testOrderByDirectCallableComparator(): void
    {
        $result = LinqPHP::from($this->data)
            ->orderBy(fn(array $a, array $b): int => $a['age'] <=> $b['age'])
            ->toArray();

        $this->assertSame([20, 22, 28, 35], array_column($result, 'age'));
    }

    public function testOrderByWithMixedCriteriaFieldsAndClosures(): void
    {
        // Ordena primariamente por nome ASC e desempata por closure de score DESC
        $result = LinqPHP::from($this->data)
            ->orderBy([
                'name' => false,
                [fn(array $x): int => $x['score'], true],
            ])
            ->toArray();

        $this->assertSame('Ana', $result[0]['name']);
        $this->assertSame('Bruno', $result[1]['name']);
        // Carlos tem id 3 com score 95 e id 2 com score 90 -> 95 deve vir antes de 90
        $this->assertSame('Carlos', $result[2]['name']);
        $this->assertSame(95, $result[2]['score']);
        $this->assertSame('Carlos', $result[3]['name']);
        $this->assertSame(90, $result[3]['score']);
    }

    public function testOrderByWithAssociativeLabelAndClosure(): void
    {
        $result = LinqPHP::from($this->data)
            ->orderBy([
                'diff' => [fn(array $x): int => $x['score'] - $x['age'], 'desc'],
            ])
            ->toArray();

        $this->assertSame(['Carlos', 'Ana', 'Carlos', 'Bruno'], array_column($result, 'name'));
    }

    public function testOrderBySingleComparatorInArray(): void
    {
        $result = LinqPHP::from($this->data)
            ->orderBy([fn(array $a, array $b): int => $a['age'] <=> $b['age']])
            ->toArray();

        $this->assertSame([20, 22, 28, 35], array_column($result, 'age'));
    }

    public function testOrderByThrowsExceptionWhenComparatorCombinedWithMultipleFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Comparator callables with two parameters cannot be combined with multiple fields in orderBy.');

        LinqPHP::from($this->data)->orderBy([
            'name',
            fn(array $a, array $b): int => $a['age'] <=> $b['age'],
        ]);
    }
}
