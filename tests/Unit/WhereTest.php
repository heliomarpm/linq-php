<?php

declare(strict_types=1);

use HeliomarPM\LinqPHP\LinqPHP;
use PHPUnit\Framework\TestCase;

class WhereTest extends TestCase
{
    public function testWhereWithSimpleCondition()
    {
        $data = [
            ['id' => 1, 'age' => 20],
            ['id' => 2, 'age' => 30],
        ];

        $result = LinqPHP::from($data)
          ->where(['age', '>', 25])
          ->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['id']);
    }

    public function testWhereWithClosure()
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        $result = LinqPHP::from($data)
          ->where(fn($x) => $x['id'] % 2 === 0)
          ->toArray();

        $this->assertEquals([['id' => 2]], $result);
    }

    public function testWhereWithArrayCondition()
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
            ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
        ];

        $result = LinqPHP::from($data)
          ->where([
              ['age', '>=', 30],
              ['status', '=', 'pending'],
          ])->toArray();

        $expectedResult = [
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testWhereWithFunctionCondition()
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
            ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
        ];

        $linq = new LinqPHP($data);

        $result = $linq->where(fn($item) => $item['age'] > 30)->toArray();

        $expectedResult = [
            ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testWhereWithMixedConditions()
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
            ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
        ];

        $linq = new LinqPHP($data);

        $result = $linq->where([
            ['age', '>', 25],
            fn($item) => $item['status'] === 'pending',
        ])->toArray();

        $expectedResult = [
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testWhereWithCaseInsensitiveUtf8Accents(): void
    {
        $data = [
            ['id' => 1, 'nome' => 'JOÃO SILVA', 'cidade' => 'SÃO PAULO'],
            ['id' => 2, 'nome' => 'Maçã Fresca', 'item' => 'ÁGUA MINERAL'],
        ];

        // Comparação com acentuação UTF-8 case-insensitive padrão
        $result = LinqPHP::from($data)
            ->where(['nome', '=', 'joão silva'])
            ->toArray();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);

        $resultContains = LinqPHP::from($data)
            ->where(['item', 'contains', 'água'])
            ->toArray();

        $this->assertCount(1, $resultContains);
        $this->assertSame(2, $resultContains[0]['id']);

        $resultIn = LinqPHP::from($data)
            ->where(['nome', 'in', ['maçã fresca', 'outro']])
            ->toArray();

        $this->assertCount(1, $resultIn);
        $this->assertSame(2, $resultIn[0]['id']);
    }

    public function testWhereWithCaseSensitiveExactMatch(): void
    {
        $data = [
            ['id' => 1, 'token' => 'TokenABC'],
            ['id' => 2, 'token' => 'tokenabc'],
        ];

        // Com caseSensitive: true, apenas o item com o case idêntico é retornado
        $resultUpper = LinqPHP::from($data)
            ->where(['token', '=', 'TokenABC'], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $resultUpper);
        $this->assertSame(1, $resultUpper[0]['id']);

        $resultLower = LinqPHP::from($data)
            ->where(['token', '=', 'tokenabc'], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $resultLower);
        $this->assertSame(2, $resultLower[0]['id']);

        // Com caseSensitive: false (padrão), ambos combinam com a busca
        $resultDefault = LinqPHP::from($data)
            ->where(['token', '=', 'TOKENABC'])
            ->toArray();

        $this->assertCount(2, $resultDefault);
    }

    public function testWhereWithCaseSensitiveOperators(): void
    {
        $data = [
            ['id' => 1, 'sku' => 'PROD-A-100'],
            ['id' => 2, 'sku' => 'prod-b-200'],
        ];

        // startswith sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'startswith', 'PROD-A'], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'startswith', 'prod-a'], caseSensitive: true)->toArray());

        // contains sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'contains', 'A-100'], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'contains', 'a-100'], caseSensitive: true)->toArray());

        // endswith sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'endswith', '200'], caseSensitive: true)->toArray());

        // in sensível
        $this->assertCount(1, LinqPHP::from($data)->where(['sku', 'in', ['PROD-A-100', 'INEXISTENTE']], caseSensitive: true)->toArray());
        $this->assertCount(0, LinqPHP::from($data)->where(['sku', 'in', ['prod-a-100']], caseSensitive: true)->toArray());
    }

    public function testWhereWithCaseSensitiveMultipleConditions(): void
    {
        $data = [
            ['id' => 1, 'code' => 'ALPHA', 'category' => 'TOOLS'],
            ['id' => 2, 'code' => 'alpha', 'category' => 'tools'],
        ];

        $result = LinqPHP::from($data)
            ->where([
                ['code', '=', 'ALPHA'],
                ['category', '=', 'TOOLS'],
            ], caseSensitive: true)
            ->toArray();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    public function testWhereWithNotEqualsOperators(): void
    {
        $data = [
            ['id' => 1, 'status' => 'ativo', 'role' => 'admin'],
            ['id' => 2, 'status' => 'bloqueado', 'role' => 'user'],
            ['id' => 3, 'status' => 'ativo', 'role' => 'guest'],
        ];

        // Operador !=
        $notBlocked = LinqPHP::from($data)->where(['status', '!=', 'bloqueado'])->toArray();
        $this->assertCount(2, $notBlocked);
        $this->assertSame([1, 3], array_column($notBlocked, 'id'));

        // Operador <> (sinônimo SQL)
        $notAdmin = LinqPHP::from($data)->where(['role', '<>', 'admin'])->toArray();
        $this->assertCount(2, $notAdmin);
        $this->assertSame([2, 3], array_column($notAdmin, 'id'));

        // Operador !== (estrito)
        $numbers = [
            ['id' => 1, 'valor' => 0],
            ['id' => 2, 'valor' => '0'],
            ['id' => 3, 'valor' => 10],
        ];
        $strictNot = LinqPHP::from($numbers)->where(['valor', '!==', 0])->toArray();
        $this->assertCount(2, $strictNot);
        $this->assertSame([2, 3], array_column($strictNot, 'id'));
    }

    public function testWhereWithStrictEqualsOperator(): void
    {
        $data = [
            ['id' => 1, 'codigo' => 0],
            ['id' => 2, 'codigo' => '0'],
            ['id' => 3, 'codigo' => false],
            ['id' => 4, 'codigo' => null],
        ];

        // === Estrito diferencia 0 inteiro de '0' string e false booleano
        $resultInt = LinqPHP::from($data)->where(['codigo', '===', 0])->toArray();
        $this->assertCount(1, $resultInt);
        $this->assertSame(1, $resultInt[0]['id']);

        $resultString = LinqPHP::from($data)->where(['codigo', '===', '0'])->toArray();
        $this->assertCount(1, $resultString);
        $this->assertSame(2, $resultString[0]['id']);
    }

    public function testWhereWithStrictInOperator(): void
    {
        $data = [
            ['id' => 1, 'valor' => 0],
            ['id' => 2, 'valor' => '0'],
            ['id' => 3, 'valor' => 'ativo'],
            ['id' => 4, 'valor' => false],
        ];

        // in com strict: true não deve coincidir 0 com 'ativo' nem com false
        $result = LinqPHP::from($data)->where(['valor', 'in', [0, 100]])->toArray();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    public function testWhereWithNotInOperator(): void
    {
        $data = [
            ['id' => 1, 'status' => 'aberto'],
            ['id' => 2, 'status' => 'concluido'],
            ['id' => 3, 'status' => 'cancelado'],
            ['id' => 4, 'status' => 'pendente'],
        ];

        $result = LinqPHP::from($data)
            ->where(['status', 'not in', ['cancelado', 'pendente']])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertSame([1, 2], array_column($result, 'id'));
    }

    public function testWhereWithBetweenAndNotBetween(): void
    {
        $data = [
            ['id' => 1, 'preco' => 10.0],
            ['id' => 2, 'preco' => 25.5],
            ['id' => 3, 'preco' => 50.0],
            ['id' => 4, 'preco' => 80.0],
            ['id' => 5, 'preco' => 100.0],
        ];

        // between 25.5 e 80.0 (inclusivo)
        $between = LinqPHP::from($data)->where(['preco', 'between', [25.5, 80.0]])->toArray();
        $this->assertCount(3, $between);
        $this->assertSame([2, 3, 4], array_column($between, 'id'));

        // not between 25.5 e 80.0
        $notBetween = LinqPHP::from($data)->where(['preco', 'not between', [25.5, 80.0]])->toArray();
        $this->assertCount(2, $notBetween);
        $this->assertSame([1, 5], array_column($notBetween, 'id'));
    }

    public function testWhereWithLargeInArrayOptimization(): void
    {
        $largeList = range(100, 5000);
        $dataset = [
            ['id' => 1, 'codigo' => 50],
            ['id' => 2, 'codigo' => 100],
            ['id' => 3, 'codigo' => 2500],
            ['id' => 4, 'codigo' => 5000],
            ['id' => 5, 'codigo' => 9999],
        ];

        $result = LinqPHP::from($dataset)
            ->where(['codigo', 'in', $largeList])
            ->toArray();

        $this->assertCount(3, $result);
        $this->assertSame([2, 3, 4], array_column($result, 'id'));

        $notInResult = LinqPHP::from($dataset)
            ->where(['codigo', 'not in', $largeList])
            ->toArray();

        $this->assertCount(2, $notInResult);
        $this->assertSame([1, 5], array_column($notInResult, 'id'));
    }

    public function testWhereWithInCollisionKeysAndStrictTypes(): void
    {
        $dataset = [
            ['id' => 1, 'val' => 0],
            ['id' => 2, 'val' => '0'],
            ['id' => 3, 'val' => false],
            ['id' => 4, 'val' => null],
            ['id' => 5, 'val' => '10'],
        ];

        // Lista contendo tanto o inteiro 0 quanto a string '0'
        $result = LinqPHP::from($dataset)
            ->where(['val', 'in', [0, '0']])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertSame([1, 2], array_column($result, 'id'));

        // not in exclui apenas os estritos 0 e '0'
        $notIn = LinqPHP::from($dataset)
            ->where(['val', 'not in', [0, '0']])
            ->toArray();

        $this->assertCount(3, $notIn);
        $this->assertSame([3, 4, 5], array_column($notIn, 'id'));
    }

    public function testWhereWithInNonFlippableArrayGracefulFallback(): void
    {
        $dataset = [
            ['id' => 1, 'flag' => true],
            ['id' => 2, 'flag' => false],
            ['id' => 3, 'flag' => null],
            ['id' => 4, 'flag' => 1],
        ];

        // Lista com booleano não é convertida via array_flip mas deve funcionar perfeitamente via fallback
        $result = LinqPHP::from($dataset)
            ->where(['flag', 'in', [true, false]])
            ->toArray();

        $this->assertCount(2, $result);
        $this->assertSame([1, 2], array_column($result, 'id'));
    }

    public function testWhereWithUnsupportedOperatorThrowsException(): void
    {
        $data = [['id' => 1, 'nome' => 'Teste']];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported operator: regex');

        LinqPHP::from($data)->where(['nome', 'regex', '/^[a-z]+$/'])->toArray();
    }
}
