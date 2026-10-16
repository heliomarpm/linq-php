<?php

use PHPUnit\Framework\TestCase;
use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

/**
 * Testes unitários com dados mockados baseados em cenários reais de uso
 * encontrados no projeto de produção "Salvar" (camarote/salvar/page).
 */
class SalvarRealWorldTest extends TestCase
{
    /**
     * Cenário 1 (aniversariantes.php):
     * Filtro de aniversariantes válidos no mês (onde qtd_dias >= 0).
     */
    public function testAniversariantesFilterUpcomingBirthdays(): void
    {
        // Arrange
        $mockBirthdays = [
            [
                'id_cli' => 10,
                'nome_cli' => 'Carlos Silva',
                'dt_nasc' => '1990-10-01',
                'qtd_dias' => -12,
                'desc_quando' => 'passou',
                'whatsapp' => '5571999990001',
            ],
            [
                'id_cli' => 20,
                'nome_cli' => 'Mariana Souza',
                'dt_nasc' => '1995-10-13',
                'qtd_dias' => 0,
                'desc_quando' => 'hoje',
                'whatsapp' => '5571999990002',
            ],
            [
                'id_cli' => 30,
                'nome_cli' => 'Fernando Lima',
                'dt_nasc' => '1988-10-18',
                'qtd_dias' => 5,
                'desc_quando' => 'nesta semana',
                'whatsapp' => '5571999990003',
            ],
        ];

        // Act - uso real em aniversariantes.php: LinqPHP::from(...)->where(['qtd_dias', '>=', '0'])->toObject()
        $result = LinqPHP::from($mockBirthdays)
            ->where(['qtd_dias', '>=', '0'])
            ->toObject();

        // Assert
        $this->assertSame(2, $result->count);
        $this->assertCount(2, $result->data);
        $this->assertSame('Mariana Souza', $result->data[0]['nome_cli']);
        $this->assertSame('Fernando Lima', $result->data[1]['nome_cli']);
    }

    /**
     * Cenário 2 (aniversariantes.php):
     * Agrupamento por 'desc_quando' com contagem agregada e filtro para aniversariantes de 'hoje'.
     */
    public function testAniversariantesGroupByQuandoAndFilterToday(): void
    {
        // Arrange
        $mockBirthdays = [
            ['id_cli' => 1, 'nome_cli' => 'Alice', 'qtd_dias' => 0, 'desc_quando' => 'hoje'],
            ['id_cli' => 2, 'nome_cli' => 'Bruno', 'qtd_dias' => 0, 'desc_quando' => 'hoje'],
            ['id_cli' => 3, 'nome_cli' => 'Carla', 'qtd_dias' => 4, 'desc_quando' => 'nesta semana'],
            ['id_cli' => 4, 'nome_cli' => 'Diego', 'qtd_dias' => 15, 'desc_quando' => 'este mês'],
        ];

        // Act - uso real em aniversariantes.php:
        // LinqPHP::from($rs_aniversariantes->data)->groupBy(['desc_quando'], ['count' => ['qtd_dias' => 'count']])->where(['desc_quando', '=', 'hoje'])->toArray()
        $arrNiverHoje = LinqPHP::from($mockBirthdays)
            ->groupBy(['desc_quando'], ['count' => ['qtd_dias' => 'count']])
            ->where(['desc_quando', '=', 'hoje'])
            ->toArray();

        // Assert
        $this->assertCount(1, $arrNiverHoje);
        $this->assertSame('hoje', $arrNiverHoje[0]['desc_quando']);
        $this->assertSame(2, $arrNiverHoje[0]['count']);

        $qtdHoje = $arrNiverHoje !== [] ? $arrNiverHoje[0]['count'] : 0;
        $this->assertSame(2, $qtdHoje);
    }

    /**
     * Cenário 2b (aniversariantes.php):
     * Quando não há nenhum aniversariante no dia de hoje, deve retornar array vazio.
     */
    public function testAniversariantesGroupByQuandoWithoutTodayMatches(): void
    {
        // Arrange
        $mockBirthdays = [
            ['id_cli' => 3, 'nome_cli' => 'Carla', 'qtd_dias' => 4, 'desc_quando' => 'nesta semana'],
        ];

        // Act
        $arrNiverHoje = LinqPHP::from($mockBirthdays)
            ->groupBy(['desc_quando'], ['count' => ['qtd_dias' => 'count']])
            ->where(['desc_quando', '=', 'hoje'])
            ->toArray();

        // Assert
        $this->assertSame([], $arrNiverHoje);
        $qtdHoje = $arrNiverHoje !== [] ? $arrNiverHoje[0]['count'] : 0;
        $this->assertSame(0, $qtdHoje);
    }

    /**
     * Cenário 3 (cliente.php):
     * Compras efetivadas com filtro via closure excluindo canceladas (id_statusvenda != 2)
     * e agregação global sem agrupamento (groupBy([], ...)).
     */
    public function testClienteComprasEfetivadasGlobalAggregationWithClosure(): void
    {
        // Arrange
        $mockSalesHistory = [
            ['id_venda' => 101, 'id_statusvenda' => 1, 'valor_venda' => 150.50, 'data_venda' => '2024-05-10'],
            ['id_venda' => 102, 'id_statusvenda' => 2, 'valor_venda' => 80.00,  'data_venda' => '2024-05-12'], // cancelada
            ['id_venda' => 103, 'id_statusvenda' => 1, 'valor_venda' => 249.50, 'data_venda' => '2024-05-15'],
        ];

        // Act - uso real em cliente.php:
        // LinqPHP::from($rs_compras->rows)->where(fn ($row) => $row["id_statusvenda"] != 2)->groupBy([], ['sum' => ['valor_venda'], 'count' => ['id_venda' => 'count']])->toArray()
        $efetivadas = LinqPHP::from($mockSalesHistory)
            ->where(fn (array $row): bool => $row['id_statusvenda'] != 2)
            ->groupBy([], ['sum' => ['valor_venda'], 'count' => ['id_venda' => 'count']])
            ->toArray();

        // Assert
        $this->assertCount(1, $efetivadas);
        $this->assertEquals(400.0, $efetivadas[0]['valor_venda']);
        $this->assertSame(2, $efetivadas[0]['count']);

        $quantCompras = $efetivadas[0]['count'];
        $totalCompras = $efetivadas[0]['valor_venda'];
        $this->assertSame(2, $quantCompras);
        $this->assertEquals(400.0, $totalCompras);
    }

    /**
     * Cenário 3b (cliente.php):
     * Compras efetivadas onde todas as vendas foram canceladas.
     */
    public function testClienteComprasEfetivadasWhenAllCancelled(): void
    {
        // Arrange
        $mockSalesHistory = [
            ['id_venda' => 101, 'id_statusvenda' => 2, 'valor_venda' => 150.00],
            ['id_venda' => 102, 'id_statusvenda' => 2, 'valor_venda' => 80.00],
        ];

        // Act
        $efetivadas = LinqPHP::from($mockSalesHistory)
            ->where(fn (array $row): bool => $row['id_statusvenda'] != 2)
            ->groupBy([], ['sum' => ['valor_venda'], 'count' => ['id_venda' => 'count']])
            ->toArray();

        // Assert
        $this->assertSame([], $efetivadas);
    }

    /**
     * Cenário 4 (cliente.php):
     * Créditos ativos com filtro multi-condição em array:
     * [['valor_atualcred', '>', 0], ['status_cred', '=', 'ON']] e agregação global.
     */
    public function testClienteCreditosAtivosMultiConditionWhereAndGlobalAggregation(): void
    {
        // Arrange
        $mockCredits = [
            ['id_credito' => 1, 'valor_inicred' => 100.0, 'valor_atualcred' => 50.0,  'status_cred' => 'ON'],
            ['id_credito' => 2, 'valor_inicred' => 80.0,  'valor_atualcred' => 0.0,   'status_cred' => 'ON'],  // zerado
            ['id_credito' => 3, 'valor_inicred' => 120.0, 'valor_atualcred' => 75.0,  'status_cred' => 'OFF'], // inativo
            ['id_credito' => 4, 'valor_inicred' => 200.0, 'valor_atualcred' => 150.0, 'status_cred' => 'ON'],
        ];

        // Act - uso real em cliente.php:
        // LinqPHP::from($rs_creditos->rows)->where([['valor_atualcred', '>', 0], ['status_cred', '=', 'ON']])->groupBy([], ['sum' => ['valor_atualcred'], 'count' => ['id_credito' => 'count']])->toArray()
        $credAtivos = LinqPHP::from($mockCredits)
            ->where([['valor_atualcred', '>', 0], ['status_cred', '=', 'ON']])
            ->groupBy([], ['sum' => ['valor_atualcred'], 'count' => ['id_credito' => 'count']])
            ->toArray();

        // Assert
        $this->assertCount(1, $credAtivos);
        $this->assertEquals(200.0, $credAtivos[0]['valor_atualcred']);
        $this->assertSame(2, $credAtivos[0]['count']);

        $quantCreditos = $credAtivos[0]['count'];
        $totalCreditos = $credAtivos[0]['valor_atualcred'];
        $this->assertSame(2, $quantCreditos);
        $this->assertEquals(200.0, $totalCreditos);
    }

    /**
     * Cenário 5 (cliente.php):
     * Detalhes de crédito com LEFT JOIN no catálogo de produtos, mapeando ['id_produto' => 'product_id'].
     */
    public function testClienteDetalhesCreditoLeftJoinWithProducts(): void
    {
        // Arrange
        $mockCreditItems = [
            ['id_cred_item' => 1, 'id_credito' => 10, 'id_produto' => 501, 'valor' => 120.00],
            ['id_cred_item' => 2, 'id_credito' => 10, 'id_produto' => 999, 'valor' => 45.00],  // produto inexistente no catálogo
            ['id_cred_item' => 3, 'id_credito' => 10, 'id_produto' => null, 'valor' => 15.00], // crédito manual avulso
        ];

        $mockProducts = [
            ['product_id' => 501, 'name' => 'Camisa Oficial 2024', 'price' => 199.90],
            ['product_id' => 502, 'name' => 'Boné Torcedor', 'price' => 59.90],
        ];

        // Act - uso real em cliente.php:
        // LinqPHP::from($rs_cred->rows)->join($rs_produtos, JoinType::LEFT, ['id_produto' => 'product_id'])->toObject()
        $result = LinqPHP::from($mockCreditItems)
            ->join($mockProducts, JoinType::LEFT, ['id_produto' => 'product_id'])
            ->toObject();

        // Assert
        $this->assertSame(3, $result->count);
        $this->assertCount(3, $result->data);

        // Item 1: match encontrado
        $this->assertSame(501, $result->data[0]['id_produto']);
        $this->assertSame('Camisa Oficial 2024', $result->data[0]['name']);
        $this->assertEquals(199.90, $result->data[0]['price']);

        // Item 2: sem match no catálogo, campos do produto devem ser null
        $this->assertSame(999, $result->data[1]['id_produto']);
        $this->assertNull($result->data[1]['name']);
        $this->assertNull($result->data[1]['price']);

        // Item 3: id_produto null, campos do produto null
        $this->assertNull($result->data[2]['id_produto']);
        $this->assertNull($result->data[2]['name']);
    }

    /**
     * Cenário 6 (gerenciarempresa.php):
     * Filtro de busca de loja específica por ID.
     */
    public function testGerenciarEmpresaFindStoreById(): void
    {
        // Arrange
        $mockStores = [
            [
                'id_loja' => 1,
                'id_empresa' => 10,
                'nm_loja' => 'Loja Shopping Salvador',
                'nr_cnpj' => '12.345.678/0001-90',
                'nm_cidade' => 'Salvador',
                'sigla_uf' => 'BA',
                'ic_ativo' => 1,
            ],
            [
                'id_loja' => 2,
                'id_empresa' => 10,
                'nm_loja' => 'Loja Pituba',
                'nr_cnpj' => '12.345.678/0002-71',
                'nm_cidade' => 'Salvador',
                'sigla_uf' => 'BA',
                'ic_ativo' => 1,
            ],
        ];

        // Act - uso real em gerenciarempresa.php:
        // LinqPHP::from($rs_lojas->rows)->where([['id_loja', '=', $edit_loja]])->toObject()
        $editLoja = 2;
        $result = LinqPHP::from($mockStores)
            ->where([['id_loja', '=', $editLoja]])
            ->toObject();

        // Assert
        $this->assertSame(1, $result->count);
        $this->assertSame('Loja Pituba', $result->data[0]['nm_loja']);

        // Teste de busca por loja inexistente
        $notFoundResult = LinqPHP::from($mockStores)
            ->where([['id_loja', '=', 999]])
            ->toObject();

        $this->assertSame(0, $notFoundResult->count);
        $this->assertSame([], $notFoundResult->data);
    }

    /**
     * Cenário 7 (maisvendidos.php):
     * Relatório de produtos mais vendidos com INNER JOIN entre catálogo e itens agregados.
     */
    public function testMaisVendidosInnerJoinProductsWithSalesItems(): void
    {
        // Arrange
        $mockProducts = [
            ['product_id' => 10, 'name' => 'Camisa Titular', 'categoria' => 'Vestuário'],
            ['product_id' => 20, 'name' => 'Bandeira Oficial', 'categoria' => 'Acessórios'],
            ['product_id' => 30, 'name' => 'Chaveiro Metálico', 'categoria' => 'Brindes'], // sem vendas
        ];

        $mockSalesSummary = [
            ['id_prod' => 10, 'quantidade_vendas' => 45, 'total_valor_item_cobrado' => 4500.00],
            ['id_prod' => 20, 'quantidade_vendas' => 18, 'total_valor_item_cobrado' => 900.00],
            ['id_prod' => 40, 'quantidade_vendas' => 5,  'total_valor_item_cobrado' => 150.00], // produto descontinuado fora do catálogo
        ];

        // Act - uso real em maisvendidos.php:
        // (new LinqPHP($rs_produtos->rows))->join($rs_itens->rows, JoinType::INNER, ['product_id' => 'id_prod'])->toObject()
        $registros = LinqPHP::from($mockProducts)
            ->join($mockSalesSummary, JoinType::INNER, ['product_id' => 'id_prod'])
            ->toObject();

        // Assert
        $this->assertSame(2, $registros->count);
        $this->assertCount(2, $registros->data);

        // Registro 1
        $this->assertSame(10, $registros->data[0]['product_id']);
        $this->assertSame('Camisa Titular', $registros->data[0]['name']);
        $this->assertSame(45, $registros->data[0]['quantidade_vendas']);
        $this->assertEquals(4500.00, $registros->data[0]['total_valor_item_cobrado']);

        // Registro 2
        $this->assertSame(20, $registros->data[1]['product_id']);
        $this->assertSame('Bandeira Oficial', $registros->data[1]['name']);
        $this->assertSame(18, $registros->data[1]['quantidade_vendas']);
    }

    /**
     * Cenário 8 (vendaetapa1.php):
     * Top vendas da semana com INNER JOIN utilizando campo com mesmo nome em ambos os lados (['product_id']).
     */
    public function testVendaEtapa1TopVendasInnerJoinWithIdenticalFieldNames(): void
    {
        // Arrange
        $mockTopSales = [
            ['product_id' => 101, 'qty_sold' => 35],
            ['product_id' => 102, 'qty_sold' => 20],
            ['product_id' => 103, 'qty_sold' => 12],
        ];

        $mockInventory = [
            ['product_id' => 101, 'name' => 'Copo Térmico 500ml', 'estoque' => 14, 'preco' => 89.90],
            ['product_id' => 102, 'name' => 'Boné Aba Reta',      'estoque' => 8,  'preco' => 69.90],
            // produto 103 fora de estoque / suprimido no catálogo
        ];

        // Act - uso real em vendaetapa1.php:
        // LinqPHP::from($rs_vendas->rows)->join($rs_prod->rows, JoinType::INNER, ['product_id'])->toObject()
        $rsTopVendas = LinqPHP::from($mockTopSales)
            ->join($mockInventory, JoinType::INNER, ['product_id'])
            ->toObject();

        // Assert
        $this->assertSame(2, $rsTopVendas->count);
        $this->assertSame(101, $rsTopVendas->data[0]['product_id']);
        $this->assertSame(35, $rsTopVendas->data[0]['qty_sold']);
        $this->assertSame('Copo Térmico 500ml', $rsTopVendas->data[0]['name']);
        $this->assertSame(14, $rsTopVendas->data[0]['estoque']);

        $this->assertSame(102, $rsTopVendas->data[1]['product_id']);
        $this->assertSame(20, $rsTopVendas->data[1]['qty_sold']);
        $this->assertSame('Boné Aba Reta', $rsTopVendas->data[1]['name']);
    }

    /**
     * Cenário 9 (salvar/tests/funcoes.test.php):
     * Caso de teste original do projeto Salvar validando LEFT JOIN entre usuários e pedidos.
     */
    public function testSalvarLegacyTestUsersAndOrdersLeftJoin(): void
    {
        // Arrange
        $usuarios = [
            ['id' => 1, 'nome' => 'Usuário 1'],
            ['id' => 2, 'nome' => 'Usuário 2'],
            ['id' => 3, 'nome' => 'Usuário 3'],
        ];
        $pedidos = [
            ['id_usuario' => 1, 'valor' => 100],
        ];

        // Act - uso original: (new LinqPHP($usuarios))->join($pedidos, 'LEFT', ['id' => 'id_usuario'])->toObject()
        $result = LinqPHP::from($usuarios)
            ->join($pedidos, JoinType::LEFT, ['id' => 'id_usuario'])
            ->toObject();

        // Assert
        $this->assertSame(3, $result->count);
        $this->assertSame(1, $result->data[0]['id']);
        $this->assertSame(100, $result->data[0]['valor']);

        $this->assertSame(2, $result->data[1]['id']);
        $this->assertNull($result->data[1]['valor']);

        $this->assertSame(3, $result->data[2]['id']);
        $this->assertNull($result->data[2]['valor']);
    }
}
