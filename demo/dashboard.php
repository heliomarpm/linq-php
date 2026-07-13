<?php
require_once __DIR__ . '/../vendor/autoload.php';

use HeliomarPM\LinqPHP\LinqPHP;
use HeliomarPM\LinqPHP\JoinType;

// ==========================================
// 1. MASSA DE DADOS (3 Tabelas Relacionais)
// ==========================================
$clientes = [
  ['id_cliente' => 1, 'nome_cliente' => 'Ana Sousa', 'uf' => 'MG', 'vip' => true],
  ['id_cliente' => 2, 'nome_cliente' => 'Carlos Lima', 'uf' => 'SP', 'vip' => false],
  ['id_cliente' => 3, 'nome_cliente' => 'Helio', 'uf' => 'MG', 'vip' => true],
  ['id_cliente' => 4, 'nome_cliente' => 'Sem Compras', 'uf' => 'RJ', 'vip' => false], // Cliente sem compras
];

$produtos = [
  ['id_produto' => 101, 'nome_produto' => 'PLA Preto 1kg', 'categoria' => 'Insumo', 'preco' => 89.90],
  ['id_produto' => 102, 'nome_produto' => 'PETG Branco 1kg', 'categoria' => 'Insumo', 'preco' => 95.50],
  ['id_produto' => 103, 'nome_produto' => 'Bico Extrusor 0.4mm', 'categoria' => 'Peça', 'preco' => 35.00],
  ['id_produto' => 104, 'nome_produto' => 'Mesa PEI Texturizada', 'categoria' => 'Peça', 'preco' => 120.00],
  ['id_produto' => 105, 'nome_produto' => 'Resina 8K (Lançamento)', 'categoria' => 'Insumo', 'preco' => 180.00], // Produto sem vendas
];

$vendas = [
  ['id_venda' => 1, 'produto_id' => 101, 'cliente_id' => 1, 'qtd' => 2, 'status' => 'concluido'],
  ['id_venda' => 2, 'produto_id' => 103, 'cliente_id' => 3, 'qtd' => 5, 'status' => 'concluido'],
  ['id_venda' => 3, 'produto_id' => 101, 'cliente_id' => 2, 'qtd' => 1, 'status' => 'pendente'],
  ['id_venda' => 4, 'produto_id' => 102, 'cliente_id' => 1, 'qtd' => 3, 'status' => 'concluido'],
  ['id_venda' => 5, 'produto_id' => 999, 'cliente_id' => 3, 'qtd' => 2, 'status' => 'concluido'], // Produto deletado (órfão)
];

// ==========================================
// 2. DEFINIÇÃO DOS CENÁRIOS DE TESTE AVANÇADOS
// ==========================================
$cenarios = [
  'join_triplo' => [
    'titulo' => 'Join Triplo + Multi-Filtros',
    'desc' => 'Une Vendas, Produtos e Clientes, filtrando apenas clientes VIPs com vendas concluídas.',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->where(['status', '=', 'concluido'])
    ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
    ->join(\$clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
    ->where(['vip', '=', true])
    ->select(['id_venda', 'nome_cliente', 'nome_produto', 'qtd', 'preco'])
    ->orderByKey('id_venda', 'asc')
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->where(['status', '=', 'concluido'])
      ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
      ->join($clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
      ->where(['vip', '=', true])
      ->select(['id_venda', 'nome_cliente', 'nome_produto', 'qtd', 'preco'])
      ->orderByKey('id_venda', 'asc')
      ->toObject()
  ],

  'todas_agregacoes' => [
    'titulo' => 'Agregações Completas (GroupBy)',
    'desc' => 'Agrupa produtos por categoria e extrai: Soma(qtd), Contagem(vendas), Max(preco), Min(preco) e Avg(preco).',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
    ->groupBy(['categoria'], [
        'sum'   => ['qtd'],
        'count' => ['id_venda'],
        'max'   => ['preco'],
        'min'   => ['preco'],
        'avg'   => ['preco']
    ])
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
      ->groupBy(['categoria'], [
        'sum' => ['qtd'],
        'count' => ['id_venda'],
        'max' => ['preco'],
        'min' => ['preco'],
        'avg' => ['preco']
      ])
      ->toObject()
  ],

  'full_join' => [
    'titulo' => 'Full Outer Join (Orfãos)',
    'desc' => 'Mostra TUDO. Revela o "Produto 999" que foi vendido mas não existe no catálogo, e a "Resina 8K" que existe mas não vendeu.',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->join(\$produtos, JoinType::FULL, ['produto_id' => 'id_produto'])
    ->select(['id_venda', 'produto_id', 'nome_produto', 'qtd'])
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->join($produtos, JoinType::FULL, ['produto_id' => 'id_produto'])
      ->select(['id_venda', 'produto_id', 'nome_produto', 'qtd'])
      ->toObject()
  ],

  'right_join' => [
    'titulo' => 'Right Join (Clientes Ociosos)',
    'desc' => 'Une Vendas com Clientes (RIGHT). Revela clientes cadastrados que nunca fizeram uma compra.',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->join(\$clientes, JoinType::RIGHT, ['cliente_id' => 'id_cliente'])
    ->select(['id_cliente', 'nome_cliente', 'id_venda', 'status'])
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->join($clientes, JoinType::RIGHT, ['cliente_id' => 'id_cliente'])
      ->select(['id_cliente', 'nome_cliente', 'id_venda', 'status'])
      ->toObject()
  ],

  'distinct_uf' => [
    'titulo' => 'Distinct + Projeção Simples',
    'desc' => 'Quais Estados (UF) já compraram produtos da categoria "Peça"?',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
    ->join(\$clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
    ->where(['categoria', '=', 'Peça'])
    ->select(['uf'])
    ->distinct()
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
      ->join($clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
      ->where(['categoria', '=', 'Peça'])
      ->select(['uf'])
      ->distinct()
      ->toObject()
  ],

  'complex_closure' => [
    'titulo' => 'Filtro Complexo (Closure)',
    'desc' => 'Usa função anônima para regras matemáticas: Vendas de Insumos cujo Faturamento (qtd * preco) foi maior que R$ 100,00.',
    'codigo' => <<<PHP
\$linq = LinqPHP::from(\$vendas)
    ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
    ->where(function(\$row) {
        if (\$row['categoria'] !== 'Insumo') return false;
        \$faturamento = \$row['qtd'] * \$row['preco'];
        return \$faturamento > 100.00;
    })
    ->select(['nome_produto', 'qtd', 'preco'])
    ->toObject();
PHP,
    'run' => fn() => LinqPHP::from($vendas)
      ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
      ->where(function ($row) {
        if ($row['categoria'] !== 'Insumo')
          return false;
        $faturamento = $row['qtd'] * $row['preco'];
        return $faturamento > 100.00;
      })
      ->select(['nome_produto', 'qtd', 'preco'])
      ->toObject()
  ]
];

// Identifica o cenário atual pela URL
$cenarioAtual = $_GET['cenario'] ?? 'join_triplo';
if (!array_key_exists($cenarioAtual, $cenarios)) {
  $cenarioAtual = 'join_triplo';
}

$dadosCenario = $cenarios[$cenarioAtual];
$linq = $dadosCenario['run']();
$resultado = $linq->data;

// ==========================================
// 3. FUNÇÃO DE RENDERIZAÇÃO HTML
// ==========================================
function renderGrid(string $titulo, array $dados, string $corHeader = 'bg-primary')
{
  if (empty($dados)) {
    return "<div class='alert alert-warning'>Nenhum dado retornado para este cenário.</div>";
  }
  $colunas = array_keys($dados[0]);
  $html = "<div class='card shadow-sm mb-4'>";
  $html .= "<div class='card-header text-white {$corHeader}'><h6 class='mb-0'>{$titulo}</h6></div>";
  $html .= "<div class='card-body p-0'><div class='table-responsive'><table class='table table-sm table-striped table-hover mb-0' style='font-size: 0.9rem;'>";
  $html .= "<thead><tr>";
  foreach ($colunas as $col) {
    $html .= "<th>" . strtoupper($col) . "</th>";
  }
  $html .= "</tr></thead><tbody>";
  foreach ($dados as $linha) {
    $html .= "<tr>";
    foreach ($colunas as $col) {
      $valor = $linha[$col] ?? '<em class="text-muted">null</em>';
      if (is_bool($valor))
        $valor = $valor ? 'Sim' : 'Não';
      if (is_float($valor))
        $valor = number_format($valor, 2, ',', '.');
      $html .= "<td>{$valor}</td>";
    }
    $html .= "</tr>";
  }
  $html .= "</tbody></table></div></div></div>";
  return $html;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LinqPHP - Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f1f3f5;
      padding-bottom: 50px;
    }

    .performance-bar {
      font-size: 0.9rem;
    }

    .nav-pills .nav-link.active {
      background-color: #6f42c1;
    }

    .data-sources {
      max-height: 80vh;
      overflow-y: auto;
      padding-right: 10px;
    }

    .data-sources::-webkit-scrollbar {
      width: 6px;
    }

    .data-sources::-webkit-scrollbar-thumb {
      background-color: #adb5bd;
      border-radius: 10px;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-dark mb-4 shadow-sm" style="background-color: #6f42c1;">
    <div class="container-fluid">
      <span class="navbar-brand mb-0 h1">🧪 LinqPHP - Laboratório Avançado</span>
    </div>
  </nav>

  <div class="container-fluid px-4">

    <div class="row mb-4">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body bg-white rounded">
            <ul class="nav nav-pills justify-content-center">
              <?php foreach ($cenarios as $key => $cenario): ?>
                <li class="nav-item me-2 mb-2">
                  <a class="nav-link <?= $key === $cenarioAtual ? 'active shadow' : 'bg-light text-dark border' ?>"
                    href="?cenario=<?= $key ?>">
                    <?= $cenario['titulo'] ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Coluna da Esquerda: Arrays de Origem (Com Scroll) -->
      <div class="col-lg-4 data-sources">
        <h5 class="mb-3 text-secondary">🗄️ Arrays de Origem</h5>
        <?= renderGrid('Clientes', $clientes, 'bg-secondary') ?>
        <?= renderGrid('Produtos', $produtos, 'bg-secondary') ?>
        <?= renderGrid('Vendas', $vendas, 'bg-secondary') ?>
      </div>

      <!-- Coluna da Direita: Resultado -->
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="text-dark mb-0">🎯 Resultado: <?= $dadosCenario['titulo'] ?></h5>
        </div>

        <p class="text-muted"><?= $dadosCenario['desc'] ?></p>

        <div class="alert alert-dark performance-bar d-flex justify-content-between align-items-center shadow-sm">
          <span><strong>Metadados de Execução</strong></span>
          <span class="badge bg-primary rounded-pill fs-6">
            ⏱️ <?= $linq->elapsedTime ?>s | 💾 <?= $linq->memoryUsed ?> | 📝 <?= $linq->count ?> linhas
          </span>
        </div>

        <?= renderGrid('Dados de Saída', $resultado, 'bg-dark') ?>

        <div class="card mt-4 shadow-sm border-0">
          <div class="card-header text-white" style="background-color: #2b3035;">
            <small>💻 Código Executado</small>
          </div>
          <div class="card-body bg-dark text-light rounded-bottom">
            <pre class="mb-0"
              style="white-space: pre-wrap;"><code class="text-light"><?= htmlspecialchars($dadosCenario['codigo']) ?></code></pre>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>

</html>
