<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;

class SelectTest extends TestCase
{
    public function testSelectStrictThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        LinqPHP::from([
          ['id' => 1, 'name' => 'John']
        ])->select(['gender'], strict: true)->toArray();
    }

    public function testSelectReturnsExpectedResult()
    {
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25],
          ['id' => 2, 'name' => 'Jane', 'age' => 30],
          ['id' => 3, 'name' => 'John', 'age' => 35],
        ];

        $expectedResult = [
          ['name' => 'John', 'age' => 25],
          ['name' => 'Jane', 'age' => 30],
          ['name' => 'John', 'age' => 35],
        ];

        $result = LinqPHP::from($data)->select(['name', 'age'])->toArray();

        $this->assertEquals($expectedResult, $result);
    }

    public function testSelectReturnsEmptyArrayWhenNoMatchingKeys()
    {
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25],
          ['id' => 2, 'name' => 'Jane', 'age' => 30],
          ['id' => 3, 'name' => 'John', 'age' => 35],
        ];

        $expectedResult = [
          ['id' => 1, 'gender' => null, 'hobby' => null],
          ['id' => 2, 'gender' => null, 'hobby' => null],
          ['id' => 3, 'gender' => null, 'hobby' => null],
        ];

        $result = LinqPHP::from($data)->select(['id', 'gender', 'hobby'], false)->toArray();
        $this->assertEquals($expectedResult, $result);
    }

    public function testSelectReturnsOriginalDataWhenNoSelectorsProvided()
    {
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25],
          ['id' => 2, 'name' => 'Jane', 'age' => 30],
          ['id' => 3, 'name' => 'John', 'age' => 35],
        ];

        $result = LinqPHP::from($data)->select([])->toArray();

        $this->assertEquals($data, $result);
    }

    public function testSelectWithDistinct()
    {
        // Test distinct with an array of unique elements
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25],
          ['id' => 2, 'name' => 'Jane', 'age' => 30],
        ];
        $expected = $data;
        $result = LinqPHP::from($data)->select([])->distinct()->toArray();
        $this->assertEquals($expected, $result);

        // Test distinct with an array of duplicate elements
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25],
          ['id' => 2, 'name' => 'Jane', 'age' => 30],
          ['id' => 1, 'name' => 'John', 'age' => 25],
        ];
        $expected = [
          ['name' => 'John', 'age' => 25],
          ['name' => 'Jane', 'age' => 30],
        ];
        $result = LinqPHP::from($data)->select(['name', 'age'])->distinct()->toArray();
        $this->assertEquals($expected, $result);

        // Test distinct with an array of nested objects
        $data = [
          ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
          ['id' => 2, 'name' => 'Jane', 'age' => 30, 'address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
          ['id' => 1, 'name' => 'John', 'age' => 25, 'address' => ['street' => '123 Main St', 'city' => 'New York']],
        ];
        $expected = [
          ['address' => ['street' => '123 Main St', 'city' => 'New York']],
          ['address' => ['street' => '456 Elm St', 'city' => 'Los Angeles']],
        ];
        $result = LinqPHP::from($data)->select(['address'])->distinct()->toArray();
        $this->assertEquals($expected, $result);
    }

    public function testSelectWithAliases(): void
    {
        // Arrange
        $data = [
            ['id_venda' => 101, 'nome_produto' => 'PLA Preto 1kg', 'qtd' => 2, 'preco' => 89.90],
            ['id_venda' => 102, 'nome_produto' => 'PETG Branco 1kg', 'qtd' => 3, 'preco' => 95.50],
        ];

        // Act
        $result = LinqPHP::from($data)
            ->select([
                'id_venda'     => 'Id da Venda',
                'nome_produto' => 'Nome do Produto',
                'qtd'          => 'Quantidade',
                'preco'        => 'Preço',
            ])
            ->toArray();

        // Assert
        $expected = [
            [
                'Id da Venda'     => 101,
                'Nome do Produto' => 'PLA Preto 1kg',
                'Quantidade'      => 2,
                'Preço'           => 89.90,
            ],
            [
                'Id da Venda'     => 102,
                'Nome do Produto' => 'PETG Branco 1kg',
                'Quantidade'      => 3,
                'Preço'           => 95.50,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testSelectWithHybridAliases(): void
    {
        // Arrange
        $data = [
            ['id_venda' => 1, 'nome_produto' => 'Filamento', 'status' => 'concluido', 'preco' => 100.0],
        ];

        // Act - mistura de chaves numéricas (mantém nome) e associativas (renomeia)
        $result = LinqPHP::from($data)
            ->select([
                'id_venda' => 'id',
                'status', // mantém como 'status'
                'preco'    => 'valor_total',
            ])
            ->toArray();

        // Assert
        $this->assertSame([
            ['id' => 1, 'status' => 'concluido', 'valor_total' => 100.0],
        ], $result);
    }

    public function testSelectWithAliasesMissingKeysNullFilledWhenNotStrict(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'Alice'],
        ];

        // Act - campo inexistente com alias deve retornar null em modo não estrito
        $result = LinqPHP::from($data)
            ->select([
                'id'          => 'user_id',
                'inexistente' => 'campo_nulo',
            ])
            ->toArray();

        // Assert
        $this->assertSame([
            ['user_id' => 1, 'campo_nulo' => null],
        ], $result);
    }

    public function testSelectWithAliasesStrictThrowsExceptionOnMissingKey(): void
    {
        // Arrange
        $data = [
            ['id' => 1, 'name' => 'Bob'],
        ];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Select strict mode error. Missing keys: non_existent');

        // Act
        LinqPHP::from($data)
            ->select([
                'id'           => 'user_id',
                'non_existent' => 'alias_campo',
            ], strict: true)
            ->toArray();
    }

    public function testSelectWithAliasesPreservesOrder(): void
    {
        // Arrange
        $data = [
            ['a' => 1, 'b' => 2, 'c' => 3],
        ];

        // Act
        $result = LinqPHP::from($data)
            ->select([
                'c' => 'primeiro',
                'a' => 'segundo',
                'b' => 'terceiro',
            ])
            ->toArray();

        // Assert - a ordem das chaves na linha projetada deve ser c -> a -> b
        $this->assertSame(['primeiro', 'segundo', 'terceiro'], array_keys($result[0]));
    }
}
