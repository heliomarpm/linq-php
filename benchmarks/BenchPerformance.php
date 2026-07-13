<?php

namespace HeliomarPM\LinqPHP\Benchmark;

use HeliomarPM\LinqPHP\LinqPHP;
// use Heliomarpm\LinqPHP\JoinType;

class BenchPerformance
{
  private array $vendas;
  private array $produtos;
  private array $dadosParaDistinct;

  /**
   * Define os cenários de carga de dados.
   * O PHPBench vai rodar todos os métodos de teste para CADA um desses tamanhos.
   */
  public function provideDataSizes(): \Generator
  {
    yield '10k_registros' => ['tamanho' => 10000];
    yield '50k_registros' => ['tamanho' => 50000];
  }

  /**
   * Este método roda antes de cada benchmark, recebendo os parâmetros do provedor.
   */
  public function setUp(array $params): void
  {
    $tamanho = $params['tamanho'];
    $this->vendas = [];
    $this->produtos = [];
    $this->dadosParaDistinct = [];

    $statusOptions = ['concluido', 'pendente', 'cancelado'];

    // 1. Gera tabela de Produtos (10% do tamanho total para simular um catálogo realista)
    $qtdProdutos = max(1, (int) ($tamanho / 10));
    for ($i = 0; $i < $qtdProdutos; $i++) {
      $this->produtos[] = [
        'id_produto' => $i,
        'nome_produto' => 'Produto ' . $i,
        'categoria' => 'Categoria ' . rand(1, 5)
      ];
    }

    // 2. Gera tabela principal de Vendas e tabela para Distinct
    for ($i = 0; $i < $tamanho; $i++) {
      $produto_id = rand(0, $qtdProdutos - 1);
      $status = $statusOptions[array_rand($statusOptions)];

      $this->vendas[] = [
        'id_venda' => $i,
        'produto_id' => $produto_id,
        'valor' => rand(10, 1000) / 10,
        'status' => $status
      ];

      // Array com altíssima repetição (ótimo para testar o distinct)
      $this->dadosParaDistinct[] = [
        'produto_id' => $produto_id,
        'status' => $status
      ];
    }
  }

  /**
   * @ParamProviders({"provideDataSizes"})
   * @BeforeMethods({"setUp"})
   * @Revs(10)
   * @Iterations(3)
   */
  public function benchWhereSimples(array $params): void
  {
    LinqPHP::from($this->vendas)
      ->where(['status', '=', 'concluido'])
      ->toArray();
  }

  /**
   * @ParamProviders({"provideDataSizes"})
   * @BeforeMethods({"setUp"})
   * @Revs(10)
   * @Iterations(3)
   */
  public function benchAgrupamento(array $params): void
  {
    LinqPHP::from($this->vendas)
      ->groupBy(['produto_id'], [
        'sum' => ['valor'],
        'count' => ['id_venda']
      ])
      ->toArray();
  }

  /**
   * @ParamProviders({"provideDataSizes"})
   * @BeforeMethods({"setUp"})
   * @Revs(10)
   * @Iterations(3)
   */
  public function benchInnerJoin(array $params): void
  {
    LinqPHP::from($this->vendas)
      ->join($this->produtos, 'INNER', ['produto_id' => 'id_produto'])
      ->toArray();
  }

  /**
   * @ParamProviders({"provideDataSizes"})
   * @BeforeMethods({"setUp"})
   * @Revs(10)
   * @Iterations(3)
   */
  public function benchDistinct(array $params): void
  {
    LinqPHP::from($this->dadosParaDistinct)
      ->distinct()
      ->toArray();
  }
}
