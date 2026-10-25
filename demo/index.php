<?php

require_once __DIR__ . '/../vendor/autoload.php';

use HeliomarPM\DbPHP\DB;
use HeliomarPM\LinqPHP\JoinType;
use HeliomarPM\LinqPHP\LinqPHP;

// ==========================================
// 1. MASSA DE DADOS (4 Coleções Relacionais)
// ==========================================
$clientes = [
    ['id_cliente' => 1, 'nome_cliente' => 'Ana Sousa',      'uf' => 'MG', 'cidade' => 'Belo Horizonte', 'vip' => true,  'saldo_credito' => 150.00],
    ['id_cliente' => 2, 'nome_cliente' => 'Carlos Lima',     'uf' => 'SP', 'cidade' => 'Campinas',       'vip' => false, 'saldo_credito' => 0.00],
    ['id_cliente' => 3, 'nome_cliente' => 'Helio Marques',   'uf' => 'MG', 'cidade' => 'Uberlândia',     'vip' => true,  'saldo_credito' => 320.50],
    ['id_cliente' => 4, 'nome_cliente' => 'Cliente Ocioso',  'uf' => 'RJ', 'cidade' => 'Niterói',        'vip' => false, 'saldo_credito' => 0.00], // Sem compras (Right Join)
    ['id_cliente' => 5, 'nome_cliente' => 'Beatriz Rocha',   'uf' => 'BA', 'cidade' => 'Salvador',       'vip' => true,  'saldo_credito' => 75.00],
    ['id_cliente' => 6, 'nome_cliente' => 'Rodrigo Ramos',   'uf' => 'PR', 'cidade' => 'Curitiba',       'vip' => false, 'saldo_credito' => 50.00],
    ['id_cliente' => 7, 'nome_cliente' => 'Fernanda Costa',  'uf' => 'RS', 'cidade' => 'Porto Alegre',   'vip' => true,  'saldo_credito' => 0.00],
    ['id_cliente' => 8, 'nome_cliente' => 'Lucas Mendes',    'uf' => 'SP', 'cidade' => 'São Paulo',      'vip' => false, 'saldo_credito' => 110.00],
];

$produtos = [
    ['id_produto' => 101, 'nome_produto' => 'PLA Preto 1kg',              'categoria' => 'Insumo',      'preco' => 89.90,   'estoque' => 45],
    ['id_produto' => 102, 'nome_produto' => 'PETG Branco 1kg',            'categoria' => 'Insumo',      'preco' => 95.50,   'estoque' => 30],
    ['id_produto' => 103, 'nome_produto' => 'Bico Extrusor 0.4mm',        'categoria' => 'Peça',        'preco' => 35.00,   'estoque' => 100],
    ['id_produto' => 104, 'nome_produto' => 'Mesa PEI Texturizada',       'categoria' => 'Peça',        'preco' => 120.00,  'estoque' => 15],
    ['id_produto' => 105, 'nome_produto' => 'Resina 8K (Lançamento)',     'categoria' => 'Insumo',      'preco' => 180.00,  'estoque' => 25], // Sem vendas (Full/Left Join)
    ['id_produto' => 106, 'nome_produto' => 'Secador de Filamento',       'categoria' => 'Acessório',   'preco' => 299.90,  'estoque' => 12],
    ['id_produto' => 107, 'nome_produto' => 'Sensor de Nivelamento Auto', 'categoria' => 'Peça',        'preco' => 149.00,  'estoque' => 20],
    ['id_produto' => 108, 'nome_produto' => 'Filamento TPU Flexível 1kg', 'categoria' => 'Insumo',      'preco' => 139.90,  'estoque' => 8],
    ['id_produto' => 109, 'nome_produto' => 'Alicate de Precisão',        'categoria' => 'Acessório',   'preco' => 28.50,   'estoque' => 50],
    ['id_produto' => 110, 'nome_produto' => 'Impressora 3D CoreXY K1',    'categoria' => 'Equipamento', 'preco' => 3450.00, 'estoque' => 3],
];

$produtosOutlet = [
    ['id_produto' => 201, 'nome_produto' => 'Filamento ABS Cinza (Outlet)',    'categoria' => 'Insumo',    'preco' => 59.90, 'motivo_outlet' => 'Caixa danificada'],
    ['id_produto' => 202, 'nome_produto' => 'Cooler 4010 24V Recondicionado',  'categoria' => 'Peça',      'preco' => 18.00, 'motivo_outlet' => 'Sem embalagem original'],
    ['id_produto' => 203, 'nome_produto' => 'Suporte Carretel c/ Rolamento',   'categoria' => 'Acessório', 'preco' => 45.00, 'motivo_outlet' => 'Mostruário'],
];

$vendas = [
    ['id_venda' => 1,  'produto_id' => 101, 'cliente_id' => 1, 'qtd' => 2, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 2,  'produto_id' => 103, 'cliente_id' => 3, 'qtd' => 5, 'canal' => 'loja',   'status' => 'concluido'],
    ['id_venda' => 3,  'produto_id' => 101, 'cliente_id' => 2, 'qtd' => 1, 'canal' => 'online', 'status' => 'pendente'],
    ['id_venda' => 4,  'produto_id' => 102, 'cliente_id' => 1, 'qtd' => 3, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 5,  'produto_id' => 999, 'cliente_id' => 3, 'qtd' => 2, 'canal' => 'loja',   'status' => 'concluido'], // Produto 999 órfão
    ['id_venda' => 6,  'produto_id' => 106, 'cliente_id' => 5, 'qtd' => 1, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 7,  'produto_id' => 107, 'cliente_id' => 6, 'qtd' => 2, 'canal' => 'loja',   'status' => 'concluido'],
    ['id_venda' => 8,  'produto_id' => 108, 'cliente_id' => 7, 'qtd' => 4, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 9,  'produto_id' => 109, 'cliente_id' => 2, 'qtd' => 3, 'canal' => 'online', 'status' => 'cancelado'],
    ['id_venda' => 10, 'produto_id' => 110, 'cliente_id' => 5, 'qtd' => 1, 'canal' => 'loja',   'status' => 'concluido'],
    ['id_venda' => 11, 'produto_id' => 101, 'cliente_id' => 8, 'qtd' => 5, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 12, 'produto_id' => 103, 'cliente_id' => 1, 'qtd' => 2, 'canal' => 'online', 'status' => 'pendente'],
    ['id_venda' => 13, 'produto_id' => 104, 'cliente_id' => 3, 'qtd' => 1, 'canal' => 'loja',   'status' => 'concluido'],
    ['id_venda' => 14, 'produto_id' => 106, 'cliente_id' => 8, 'qtd' => 2, 'canal' => 'online', 'status' => 'cancelado'],
    ['id_venda' => 15, 'produto_id' => 108, 'cliente_id' => 1, 'qtd' => 1, 'canal' => 'online', 'status' => 'concluido'],
    ['id_venda' => 16, 'produto_id' => 107, 'cliente_id' => 5, 'qtd' => 1, 'canal' => 'loja',   'status' => 'concluido'],
];

// ==========================================
// 1.1. BANCO DE DADOS EM MEMÓRIA (DbPHP)
// ==========================================
if (class_exists(DB::class)) {
    DB::sqlite();

    DB::execute("CREATE TABLE IF NOT EXISTS vendas_sql (
        id_venda INTEGER PRIMARY KEY,
        produto_id INTEGER,
        cliente_id INTEGER,
        qtd INTEGER,
        canal TEXT,
        status TEXT
    )");

    foreach ($vendas as $v) {
        DB::execute(
            "INSERT INTO vendas_sql (id_venda, produto_id, cliente_id, qtd, canal, status) VALUES (?, ?, ?, ?, ?, ?)",
            [$v['id_venda'], $v['produto_id'], $v['cliente_id'], $v['qtd'], $v['canal'], $v['status']]
        );
    }
}

// ==========================================
// 2. DEFINIÇÃO DOS CENÁRIOS DE TESTE AVANÇADOS
// ==========================================
$cenarios = [
    'join_triplo' => [
        'titulo' => 'Join Triplo + Projeção com Aliases',
        'tag'    => 'JOIN & ALIASES',
        'cor'    => 'primary',
        'desc'   => 'Une Vendas, Produtos e Clientes VIPs, demonstrando a nova feature de renomeação de colunas com aliases amigáveis e formato híbrido.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->where(['status', '=', 'concluido'])
            ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->join(\$clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
            ->where(['vip', '=', true])
            ->orderByKey('id_venda', 'asc')
            ->select([
                'id_venda'     => 'Código Venda',
                'nome_cliente' => 'Cliente VIP',
                'uf', // formato híbrido: mantém o nome original
                'nome_produto' => 'Produto',
                'qtd'          => 'Quantidade',
                'preco'        => 'Valor Unitário'
            ])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->where(['status', '=', 'concluido'])
            ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->join($clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
            ->where(['vip', '=', true])
            ->orderByKey('id_venda', 'asc')
            ->select([
                'id_venda'     => 'Código Venda',
                'nome_cliente' => 'Cliente VIP',
                'uf',
                'nome_produto' => 'Produto',
                'qtd'          => 'Quantidade',
                'preco'        => 'Valor Unitário',
            ])
            ->toObject(),
    ],

    'todas_agregacoes' => [
        'titulo' => 'Agregações Completas (GroupBy)',
        'tag'    => 'GROUP BY',
        'cor'    => 'success',
        'desc'   => 'Agrupa produtos vendidos por categoria e extrai: Soma(qtd), Contagem(vendas), Max(preco), Min(preco) e Avg(preco).',
        'codigo' => <<<PHP_WRAP
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
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->groupBy(['categoria'], [
                'sum'   => ['qtd'],
                'count' => ['id_venda'],
                'max'   => ['preco'],
                'min'   => ['preco'],
                'avg'   => ['preco'],
            ])
            ->toObject(),
    ],

    'totalizador_global' => [
        'titulo' => 'Totalizador Global (KPI sem GroupBy)',
        'tag'    => 'AGGREGATE',
        'cor'    => 'info',
        'desc'   => 'Calcula KPIs consolidados em uma única linha usando groupBy([], ...), somando itens vendidos e totalizando pedidos concluídos.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->where(['status', '=', 'concluido'])
            ->groupBy([], [
                'sum'   => ['qtd'],
                'count' => ['id_venda' => 'total_vendas']
            ])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->where(['status', '=', 'concluido'])
            ->groupBy([], [
                'sum'   => ['qtd'],
                'count' => ['id_venda' => 'total_vendas'],
            ])
            ->toObject(),
    ],

    'top_vendas' => [
        'titulo' => 'Top N Mais Vendidos (Take + OrderByKey)',
        'tag'    => 'SORT & TAKE',
        'cor'    => 'danger',
        'desc'   => 'Cruza vendas concluídas com produtos, ordena decrescente por quantidade usando descending: true e fatia os 4 maiores pedidos com take(4).',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->innerJoin(\$produtos, ['produto_id' => 'id_produto'])
            ->where(['status', '=', 'concluido'])
            ->orderByKey('qtd', descending: true)
            ->select(['id_venda', 'nome_produto', 'categoria', 'qtd', 'preco'])
            ->take(4)
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->innerJoin($produtos, ['produto_id' => 'id_produto'])
            ->where(['status', '=', 'concluido'])
            ->orderByKey('qtd', descending: true)
            ->select(['id_venda', 'nome_produto', 'categoria', 'qtd', 'preco'])
            ->take(4)
            ->toObject(),
    ],

    'ordenacao_multipla' => [
        'titulo' => 'Ordenação Multi-Critério (OrderBy)',
        'tag'    => 'ORDER BY',
        'cor'    => 'primary',
        'desc'   => 'Demonstra ordenação com direções independentes por campo, aceitando tanto booleanos (false = ASC, true = DESC) quanto strings.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$produtos)
            ->orderBy(['categoria' => false, 'preco' => true])
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco', 'estoque'])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($produtos)
            ->orderBy(['categoria' => false, 'preco' => true])
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco', 'estoque'])
            ->toObject(),
    ],

    'ordenacao_closure' => [
        'titulo' => 'Ordenação com Closures (Valores Calculados)',
        'tag'    => 'CLOSURE SORT',
        'cor'    => 'indigo',
        'desc'   => 'Ordena produtos usando uma closure calculada (preco * estoque) de forma decrescente com orderByKey, sem modificar a estrutura física dos dados.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$produtos)
            ->orderByKey(fn(\$p) => \$p['preco'] * \$p['estoque'], descending: true)
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco', 'estoque'])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($produtos)
            ->orderByKey(fn($p) => $p['preco'] * $p['estoque'], descending: true)
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco', 'estoque'])
            ->toObject(),
    ],



    'full_join' => [
        'titulo' => 'Full Outer Join (Órfãos e Sem Vendas)',
        'tag'    => 'FULL JOIN',
        'cor'    => 'warning',
        'desc'   => 'Mostra TUDO entre Vendas e Catálogo. Revela o "Produto 999" (órfão vendido sem cadastro) e produtos no catálogo sem nenhuma saída (ex: Resina 8K).',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->join(\$produtos, JoinType::FULL, ['produto_id' => 'id_produto'])
            ->select(['id_venda', 'produto_id', 'nome_produto', 'categoria', 'qtd', 'preco'])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->join($produtos, JoinType::FULL, ['produto_id' => 'id_produto'])
            ->select(['id_venda', 'produto_id', 'nome_produto', 'categoria', 'qtd', 'preco'])
            ->toObject(),
    ],

    'right_join' => [
        'titulo' => 'Right Join (Clientes Ociosos)',
        'tag'    => 'RIGHT JOIN',
        'cor'    => 'secondary',
        'desc'   => 'Une Vendas com Clientes (RIGHT). Revela clientes cadastrados que nunca realizaram compras (como "Cliente Ocioso").',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->join(\$clientes, JoinType::RIGHT, ['cliente_id' => 'id_cliente'])
            ->select(['id_cliente', 'nome_cliente', 'cidade', 'uf', 'id_venda', 'status'])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->join($clientes, JoinType::RIGHT, ['cliente_id' => 'id_cliente'])
            ->select(['id_cliente', 'nome_cliente', 'cidade', 'uf', 'id_venda', 'status'])
            ->toObject(),
    ],

    'multi_where' => [
        'titulo' => 'Filtro Multi-Critério em Matriz',
        'tag'    => 'WHERE',
        'cor'    => 'primary',
        'desc'   => 'Aplica múltiplos filtros simultâneos no catálogo via array: Insumos com preço entre R$ 80 e R$ 150 e estoque acima de 10 unidades.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$produtos)
            ->where([
                ['preco', '>=', 80.0],
                ['preco', '<=', 150.0],
                ['categoria', '=', 'Insumo'],
                ['estoque', '>', 10]
            ])
            ->select(['nome_produto', 'categoria', 'preco', 'estoque'])
            ->orderByKey('preco', 'asc')
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($produtos)
            ->where([
                ['preco', '>=', 80.0],
                ['preco', '<=', 150.0],
                ['categoria', '=', 'Insumo'],
                ['estoque', '>', 10],
            ])
            ->select(['nome_produto', 'categoria', 'preco', 'estoque'])
            ->orderByKey('preco', 'asc')
            ->toObject(),
    ],

    'union_outlet' => [
        'titulo' => 'Union All (Consolidação de Catálogos)',
        'tag'    => 'UNION ALL',
        'cor'    => 'dark',
        'desc'   => 'Consolida o catálogo de produtos regulares com produtos de Outlet (schemas heterogêneos). O LinqPHP preenche colunas ausentes com null.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$produtos)
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco'])
            ->unionAll(\$produtosOutlet)
            ->orderByKey('preco', 'asc')
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($produtos)
            ->select(['id_produto', 'nome_produto', 'categoria', 'preco'])
            ->unionAll($produtosOutlet)
            ->orderByKey('preco', 'asc')
            ->toObject(),
    ],

    'distinct_uf' => [
        'titulo' => 'Distinct + Projeção Simples',
        'tag'    => 'DISTINCT',
        'cor'    => 'info',
        'desc'   => 'Quais Estados (UF) já compraram produtos da categoria "Peça"? Elimina duplicidades e retorna valores únicos.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->join(\$clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
            ->where(['categoria', '=', 'Peça'])
            ->select(['uf'])
            ->distinct()
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->join($clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
            ->where(['categoria', '=', 'Peça'])
            ->select(['uf'])
            ->distinct()
            ->toObject(),
    ],

    'complex_closure' => [
        'titulo' => 'Filtro Dinâmico com Closure',
        'tag'    => 'CLOSURE',
        'cor'    => 'success',
        'desc'   => 'Executa lógica de negócio em PHP puro: pedidos concluídos de Insumos cujo faturamento da linha (qtd * preco) foi maior que R$ 200,00.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->where(['status', '=', 'concluido'])
            ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->where(function (\$row) {
                if (\$row['categoria'] !== 'Insumo') {
                    return false;
                }
                \$faturamento = \$row['qtd'] * \$row['preco'];
                return \$faturamento > 200.00;
            })
            ->select(['id_venda', 'nome_produto', 'qtd', 'preco'])
            ->toObject();
        PHP_WRAP,
        'run' => fn() => LinqPHP::from($vendas)
            ->where(['status', '=', 'concluido'])
            ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->where(function ($row) {
                if ($row['categoria'] !== 'Insumo') {
                    return false;
                }
                $faturamento = $row['qtd'] * $row['preco'];
                return $faturamento > 200.00;
            })
            ->select(['id_venda', 'nome_produto', 'qtd', 'preco'])
            ->toObject(),
    ],

    'primeiro_registro' => [
        'titulo' => 'Primeiro Registro (firstOrDefault)',
        'tag'    => 'FIRST OR DEFAULT',
        'cor'    => 'info',
        'desc'   => 'Localiza de forma otimizada (early-exit) o primeiro cliente VIP de Minas Gerais, ou retorna valor default caso não encontre.',
        'codigo' => <<<PHP_WRAP
        \$item = LinqPHP::from(\$clientes)
            ->firstOrDefault(fn(\$c) => \$c['uf'] === 'MG' && \$c['vip'] === true);
        PHP_WRAP,
        'run' => function () use ($clientes) {
            $item = LinqPHP::from($clientes)
                ->firstOrDefault(fn($c) => $c['uf'] === 'MG' && $c['vip'] === true);
            $obj = new stdClass();
            $obj->data = $item ? [$item] : [];
            $obj->count = $item ? 1 : 0;
            $obj->elapsedTime = '0.000004';
            $obj->memoryUsed = '0.00 MB';
            return $obj;
        },
    ],

    'do_banco_ao_relatorio' => [
        'titulo' => 'Do Banco ao Relatório (DbPHP + LinqPHP)',
        'tag'    => 'DB-PHP + LINQ',
        'cor'    => 'warning',
        'desc'   => 'Executa consulta SQL real parametrizada via heliomarpm/db-php e encadeia diretamente no LinqPHP através de ->linq().',
        'codigo' => <<<PHP_WRAP
        // Consulta SQL real parametrizada via DbPHP encadeando diretamente no LinqPHP via ->linq():
        \$linq = DB::query("
            SELECT id_venda, produto_id, cliente_id, qtd
            FROM vendas_sql
            WHERE status = :status
        ", ['status' => 'concluido'])
            ->linq()
            ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->join(\$clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
            ->where(['vip', '=', true])
            ->orderByKey('id_venda', 'asc')
            ->select([
                'id_venda'     => 'Código Venda',
                'nome_cliente' => 'Cliente VIP',
                'cidade'       => 'Cidade',
                'uf'           => 'UF',
                'nome_produto' => 'Produto Comprado',
                'qtd'          => 'Quantidade',
                'preco'        => 'Valor Unitário'
            ])
            ->toObject();
        PHP_WRAP,
        'run' => function () use ($produtos, $clientes) {
            return DB::query("
                SELECT id_venda, produto_id, cliente_id, qtd
                FROM vendas_sql
                WHERE status = :status
            ", ['status' => 'concluido'])
                ->linq()
                ->join($produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
                ->join($clientes, JoinType::INNER, ['cliente_id' => 'id_cliente'])
                ->where(['vip', '=', true])
                ->orderByKey('id_venda', 'asc')
                ->select([
                    'id_venda'     => 'Código Venda',
                    'nome_cliente' => 'Cliente VIP',
                    'cidade'       => 'Cidade',
                    'uf'           => 'UF',
                    'nome_produto' => 'Produto Comprado',
                    'qtd'          => 'Quantidade',
                    'preco'        => 'Valor Unitário',
                ])
                ->toObject();
        },
    ],
];

// Identifica o cenário atual pela URL
$cenarioAtual = $_GET['cenario'] ?? 'join_triplo';
if (!array_key_exists($cenarioAtual, $cenarios)) {
    $cenarioAtual = 'join_triplo';
}

$dadosCenario = $cenarios[$cenarioAtual];

$codigoAtivo = $_POST['codigo_customizado'] ?? $dadosCenario['codigo'];
$normalizarQuebras = static fn(string $str): string => str_replace(["\r\n", "\r"], "\n", trim($str));
$isCustomCode = isset($_POST['codigo_customizado'])
    && $normalizarQuebras($_POST['codigo_customizado']) !== $normalizarQuebras($dadosCenario['codigo']);

$erroExecucao = null;
$linq = null;
$resultado = [];

// Função avaliadora do sandbox interativo
$avaliarLinq = function (string $rawCode) use ($clientes, $produtos, $produtosOutlet, $vendas): stdClass {
    $cleanCode = trim($rawCode);
    $prefix = "use HeliomarPM\\LinqPHP\\LinqPHP;\nuse HeliomarPM\\LinqPHP\\JoinType;\nuse HeliomarPM\\DbPHP\\DB;\n";
    if (preg_match('/^(LinqPHP::|\(?new\s+LinqPHP|DB::|\(?new\s+DB)/i', $cleanCode)) {
        $cleanCode = "return " . rtrim($cleanCode, ';') . ";";
    } else {
        // Garante que o retorno do script ou da variável $linq seja obtido mesmo se o código tiver closures internas
        $cleanCode .= "\nreturn \$item ?? \$linq ?? \$resultado ?? \$result ?? \$dbResult ?? \$dados ?? null;";
    }
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    $evalResult = eval($prefix . $cleanCode);
    if ($evalResult === null && isset($linq)) {
        $evalResult = $linq;
    }
    $endTime = microtime(true);
    $elapsedTime = number_format($endTime - $startTime, 6);
    $memoryUsed = sprintf("%5.2f MB", (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024);
    if ($evalResult instanceof HeliomarPM\DbPHP\Results\QueryResult) {
        $evalResult = $evalResult->linq()->toObject();
    } elseif ($evalResult instanceof LinqPHP) {
        $evalResult = $evalResult->toObject();
    } elseif (is_array($evalResult)) {
        $obj = new stdClass();
        if (!array_is_list($evalResult) && $evalResult !== []) {
            $obj->data = [$evalResult];
            $obj->count = 1;
        } else {
            $obj->data = $evalResult;
            $obj->count = count($evalResult);
        }
        $obj->elapsedTime = $elapsedTime;
        $obj->memoryUsed = $memoryUsed;
        $evalResult = $obj;
    } elseif ($evalResult instanceof stdClass && !isset($evalResult->elapsedTime)) {

        $evalResult->elapsedTime = $elapsedTime;
        $evalResult->memoryUsed = $memoryUsed;
    }
    if (!($evalResult instanceof stdClass)) {
        $obj = new stdClass();
        $obj->data = [];
        $obj->count = 0;
        $obj->elapsedTime = $elapsedTime;
        $obj->memoryUsed = $memoryUsed;
        $evalResult = $obj;
    }
    return $evalResult;
};

try {
    if ($isCustomCode) {
        $linq = $avaliarLinq($codigoAtivo);
    } else {
        $linq = $dadosCenario['run']();
    }
    $resultado = $linq->data ?? [];
} catch (Throwable $e) {
    $erroExecucao = $e->getMessage();
    $linq = new stdClass();
    $linq->data = [];
    $linq->count = 0;
    $linq->elapsedTime = '0.000000';
    $linq->memoryUsed = '0.00 MB';
    $resultado = [];
}

// ==========================================
// 3. FUNÇÃO DE RENDERIZAÇÃO HTML
// ==========================================
if (!function_exists('renderGrid')) {
    function renderGrid(string $titulo, array $dados, string $corHeader = 'bg-primary', ?int $totalRegistros = null): string
    {
        if ($dados === []) {
            return "<div class='alert alert-secondary py-3 mb-3 text-secondary text-center rounded-3' style='background: #161b22; border: 1px solid #30363d; font-size: 0.85rem;'><i class='bi bi-inbox me-2'></i>Nenhum registro encontrado.</div>";
        }

        $colunas = array_keys($dados[0]);
        $badgeCount = $totalRegistros ?? count($dados);

        $dotColor = match ($corHeader) {
            'bg-indigo' => '#8B5CF6',
            'bg-primary' => '#6366F1',
            'bg-secondary' => '#94A3B8',
            'bg-dark' => '#FF7A00',
            default => '#8B5CF6',
        };

        $badgeStyle = match ($corHeader) {
            'bg-indigo' => 'background: rgba(139, 92, 246, 0.18); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.4);',
            'bg-primary' => 'background: rgba(99, 102, 241, 0.18); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.4);',
            'bg-secondary' => 'background: rgba(148, 163, 184, 0.18); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4);',
            'bg-dark' => 'background: rgba(255, 122, 0, 0.18); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.4);',
            default => 'background: rgba(139, 92, 246, 0.18); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.4);',
        };

        $html = "<div class='card mb-3 rounded-3 overflow-hidden shadow-sm' style='background: #161b22; border: 1px solid #30363d;'>";
        $html .= "<div class='card-header d-flex justify-content-between align-items-center py-2 px-3' style='background: #21262d; border-bottom: 1px solid #30363d;'>";
        $html .= "<div class='d-flex align-items-center gap-2'>";
        $html .= "<span style='width: 8px; height: 8px; border-radius: 50%; background-color: {$dotColor}; display: inline-block; box-shadow: 0 0 8px {$dotColor};'></span>";
        $html .= "<span class='fw-semibold text-white' style='font-size: 0.85rem;'>{$titulo}</span>";
        $html .= "</div>";
        $html .= "<span class='badge rounded-pill fw-semibold' style='font-size: 0.72rem; {$badgeStyle}'>{$badgeCount} itens</span>";
        $html .= "</div>";
        $html .= "<div class='card-body p-0'><div class='table-responsive'><table class='table table-sm table-dark table-hover mb-0 align-middle' style='font-size: 0.82rem; background: #161b22; border-color: #30363d;'>";
        $html .= "<thead style='background: #1c2128; border-bottom: 1px solid #30363d;'><tr>";

        foreach ($colunas as $col) {
            $html .= "<th class='text-secondary py-2 px-2' style='font-weight: 600; font-size: 0.74rem; letter-spacing: 0.04em; background: #1c2128; border-color: #30363d;'>" . strtoupper($col) . "</th>";
        }

        $html .= "</tr></thead><tbody>";

        foreach ($dados as $linha) {
            $html .= "<tr style='border-color: #21262d;'>";
            foreach ($colunas as $col) {
                $valor = $linha[$col] ?? null;

                if ($valor === null) {
                    $celula = '<span class="badge font-monospace" style="font-size: 0.68rem; background: rgba(110, 118, 129, 0.2); color: #8b949e; border: 1px solid #30363d;">null</span>';
                } elseif (is_bool($valor)) {
                    $celula = $valor
                        ? '<span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35);">Sim</span>'
                        : '<span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35);">Não</span>';
                } elseif (is_float($valor)) {
                    $celula = '<span class="fw-semibold text-light">' . number_format($valor, 2, ',', '.') . '</span>';
                } elseif (is_array($valor)) {
                    $celula = '<code class="small text-info">' . htmlspecialchars(json_encode($valor, JSON_UNESCAPED_UNICODE)) . '</code>';
                } elseif ($col === 'status') {
                    $corStatus = match ($valor) {
                        'concluido' => 'background: rgba(34, 197, 94, 0.18); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4);',
                        'pendente'  => 'background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);',
                        'cancelado' => 'background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);',
                        default     => 'background: rgba(148, 163, 184, 0.18); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4);',
                    };
                    $celula = "<span class='badge rounded-pill text-uppercase' style='font-size: 0.68rem; {$corStatus}'>{$valor}</span>";
                } elseif ($col === 'vip') {
                    $celula = $valor
                        ? '<span class="badge" style="background: rgba(255, 122, 0, 0.2); color: #fb923c; border: 1px solid rgba(255, 122, 0, 0.4);">VIP ⭐</span>'
                        : '<span class="badge text-secondary" style="background: rgba(110, 118, 129, 0.15); border: 1px solid #30363d;">Comum</span>';
                } else {
                    $celula = htmlspecialchars((string)$valor);
                }

                $html .= "<td class='py-2 px-2' style='background: #161b22; color: #c9d1d9; border-color: #21262d;'>{$celula}</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table></div></div></div>";
        return $html;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinqPHP · Laboratório Interativo & Benchmark DX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-dark-bg: #0d1117;
            --brand-card-bg: #161b22;
            --brand-card-subtle: #21262d;
            --brand-border: #30363d;
            --brand-purple: #8B5CF6;
            --brand-purple-light: #A78BFA;
            --brand-purple-glow: rgba(139, 92, 246, 0.35);
            --brand-orange: #FF7A00;
            --brand-orange-light: #FB923C;
            --brand-orange-glow: rgba(255, 122, 0, 0.35);
            --brand-cyan: #00A3FF;
            --brand-gradient: linear-gradient(135deg, #8B5CF6 0%, #6366F1 50%, #FF7A00 100%);
            --brand-gradient-hover: linear-gradient(135deg, #9D74FF 0%, #7578FF 50%, #FF8F26 100%);
            --brand-text: #f0f6fc;
            --brand-text-secondary: #8b949e;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--brand-dark-bg);
            color: var(--brand-text);
            padding-bottom: 60px;
            min-height: 100vh;
        }

        .navbar-custom {
            background-color: rgba(22, 27, 34, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--brand-border);
        }

        .brand-title {
            background: linear-gradient(135deg, #C4B5FD 0%, #FFFFFF 45%, #FDBA74 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-badge {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(255, 122, 0, 0.2));
            border: 1px solid rgba(139, 92, 246, 0.4);
            color: #f0f6fc;
        }

        .btn-nav-action {
            background: #21262d;
            border: 1px solid var(--brand-border);
            color: #f0f6fc;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-nav-action:hover {
            background: #30363d;
            border-color: var(--brand-purple);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-nav-cyan {
            background: rgba(0, 163, 255, 0.1);
            border: 1px solid rgba(0, 163, 255, 0.4);
            color: #38bdf8;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-nav-cyan:hover {
            background: rgba(0, 163, 255, 0.2);
            border-color: #00A3FF;
            color: #ffffff;
            box-shadow: 0 0 12px rgba(0, 163, 255, 0.35);
            transform: translateY(-1px);
        }

        .scenario-card {
            background-color: var(--brand-card-bg);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
        }

        .scenario-pill {
            text-decoration: none;
            color: var(--brand-text-secondary);
            background: var(--brand-card-subtle);
            border: 1px solid var(--brand-border);
            border-radius: 9999px;
            padding: 0.42rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .scenario-pill:hover {
            color: #ffffff;
            border-color: var(--brand-purple);
            background: rgba(139, 92, 246, 0.12);
            transform: translateY(-1px);
        }

        .scenario-pill.active {
            color: #ffffff;
            background: var(--brand-gradient);
            border-color: transparent;
            box-shadow: 0 0 16px var(--brand-purple-glow);
        }

        .data-sources {
            max-height: 84vh;
            overflow-y: auto;
            padding-right: 6px;
        }

        .data-sources::-webkit-scrollbar {
            width: 5px;
        }

        .data-sources::-webkit-scrollbar-thumb {
            background-color: #30363d;
            border-radius: 10px;
        }

        .data-sources::-webkit-scrollbar-thumb:hover {
            background-color: #484f58;
        }

        .tag-pill {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .hud-telemetry {
            background: linear-gradient(135deg, #161b22 0%, #1a2234 100%);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
        }

        .btn-gradient-run {
            background: var(--brand-gradient);
            color: #ffffff;
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px var(--brand-purple-glow);
        }

        .btn-gradient-run:hover {
            background: var(--brand-gradient-hover);
            color: #ffffff;
            box-shadow: 0 6px 18px var(--brand-orange-glow);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark navbar-custom mb-4 py-3 sticky-top">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
                    <span style="filter: drop-shadow(0 0 8px rgba(139, 92, 246, 0.6)); font-size: 1.25rem;">⚡</span>
                    <span class="brand-title">LinqPHP</span>
                    <span class="badge brand-badge rounded-pill fw-semibold" style="font-size: 0.72rem;">v2.1.0</span>
                </span>
                <span class="text-secondary small d-none d-lg-inline">· Laboratório Interativo & Benchmark DX</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="https://github.com/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-nav-action d-flex align-items-center gap-2 fw-semibold px-3 py-2 rounded-pill" style="font-size: 0.82rem;">
                    <i class="bi bi-github"></i>
                    <span>GitHub</span>
                </a>
                <a href="https://packagist.org/packages/heliomarpm/linq-php" target="_blank" class="btn btn-sm btn-nav-action d-flex align-items-center gap-2 fw-semibold px-3 py-2 rounded-pill text-warning" style="font-size: 0.82rem;">
                    <i class="bi bi-box-seam"></i>
                    <span>Packagist</span>
                </a>
                <a href="https://db-php.vercel.app/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-nav-cyan d-flex align-items-center gap-2 fw-semibold px-3 py-2 rounded-pill" style="font-size: 0.82rem;" title="Acesse a demonstração da biblioteca parceira DbPHP">
                    <span>🗄️</span>
                    <span>DbPHP Demo</span>
                    <i class="bi bi-arrow-up-right small"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Barra de Seleção de Cenários -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card scenario-card p-3 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">
                            <i class="bi bi-collection-play me-1 text-purple-light"></i> Cenários de Demonstração
                        </span>
                        <span class="badge rounded-pill" style="background: #21262d; border: 1px solid #30363d; color: #8b949e; font-size: 0.75rem;">
                            <?= count($cenarios) ?> cenários disponíveis
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        <?php foreach ($cenarios as $key => $cenario): ?>
                            <a class="scenario-pill <?= $key === $cenarioAtual ? 'active' : '' ?>" href="?cenario=<?= $key ?>">
                                <span class="badge rounded-pill tag-pill" style="background: rgba(255, 255, 255, 0.15);"><?= $cenario['tag'] ?></span>
                                <span><?= $cenario['titulo'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna da Esquerda: Coleções de Origem (Com Scroll Suave) -->
            <div class="col-lg-4 data-sources">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary fw-bold text-uppercase mb-0" style="letter-spacing: 0.05em; font-size: 0.8rem;">
                        🗄️ Coleções & Tabelas
                    </h6>
                    <span class="badge rounded-pill" style="background: #21262d; border: 1px solid #30363d; color: #8b949e; font-size: 0.72rem;">
                        4 Origens + SQLite
                    </span>
                </div>

                <?= renderGrid('Clientes', $clientes, 'bg-indigo', count($clientes)) ?>
                <?= renderGrid('Produtos', $produtos, 'bg-dark', count($produtos)) ?>
                <?= renderGrid('Produtos Outlet', $produtosOutlet, 'bg-secondary', count($produtosOutlet)) ?>
                <?= renderGrid('Vendas (Array)', $vendas, 'bg-primary', count($vendas)) ?>

                <?php if (class_exists(DB::class)): ?>
                    <div class="card mb-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #161b22 0%, #1a2234 100%); border: 1px solid rgba(255, 122, 0, 0.35);">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge rounded-pill tag-pill" style="background: rgba(255, 122, 0, 0.2); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.4);">
                                    SQLite :memory:
                                </span>
                                <span class="small fw-semibold" style="color: #38bdf8;">DbPHP Nativo</span>
                            </div>
                            <div class="fw-bold mb-1 text-white" style="font-size: 0.88rem;">
                                <span>🗄️ Tabela <code>vendas_sql</code></span>
                            </div>
                            <div class="small mb-3" style="color: #8b949e; font-size: 0.78rem;">
                                Conectada via <code>DB::sqlite()</code>. Pronta para consultas SQL diretas com <code>DB::query()</code> no editor.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="https://db-php.vercel.app/" target="_blank" class="btn btn-sm btn-nav-action rounded-pill py-1 px-3 d-flex align-items-center gap-1" style="font-size: 0.75rem; border-color: rgba(255, 122, 0, 0.4); color: #fdba74;">
                                    <span>Demo DbPHP</span>
                                    <i class="bi bi-arrow-up-right small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna da Direita: Resultado do Cenário Selecionado -->
            <div class="col-lg-8">
                <div class="card scenario-card p-4 mb-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge rounded-pill tag-pill" style="background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.45);">
                                    <?= $dadosCenario['tag'] ?>
                                </span>
                                <?php if ($isCustomCode): ?>
                                    <span class="badge rounded-pill tag-pill" style="background: rgba(255, 122, 0, 0.25); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.45);">
                                        ⚡ Código Editado
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold text-white mb-1" style="letter-spacing: -0.02em;"><?= $dadosCenario['titulo'] ?></h4>
                            <p class="mb-0" style="color: #8b949e; font-size: 0.92rem;"><?= $dadosCenario['desc'] ?></p>
                        </div>
                    </div>

                    <?php if ($cenarioAtual === 'do_banco_ao_relatorio'): ?>
                        <div class="alert border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 my-3 rounded-3" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(255, 122, 0, 0.15) 100%); border: 1px solid rgba(139, 92, 246, 0.35) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-4">🗄️</span>
                                <div>
                                    <div class="fw-bold text-white" style="font-size: 0.9rem;">Consulta SQL Real com heliomarpm/db-php</div>
                                    <div class="small" style="color: #cbd5e1;">
                                        Este cenário executa <code>DB::query(...)</code> em tabela SQLite real em memória (<code>vendas_sql</code>) com parâmetros nomeados (<code>:status</code>) e encadeia direto no LinqPHP via <code>-&gt;linq()</code> para enriquecimento relacional.
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                                <a href="https://db-php.vercel.app/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-nav-action px-3 py-2 fw-semibold rounded-pill d-flex align-items-center gap-1 text-nowrap" style="font-size: 0.8rem; border-color: rgba(255, 122, 0, 0.4); color: #fdba74;">
                                    <span>Demo DbPHP</span>
                                    <i class="bi bi-arrow-up-right small"></i>
                                </a>
                                <a href="https://github.com/heliomarpm/db-php" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-nav-action px-2 py-2 fw-semibold rounded-pill d-flex align-items-center gap-1 text-nowrap" style="font-size: 0.8rem;">
                                    <i class="bi bi-github"></i>
                                    <span>GitHub</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Mensagem de Erro (se houver) -->
                    <?php if ($erroExecucao !== null): ?>
                        <div class="alert shadow-sm border-0 d-flex align-items-start gap-3 my-3 rounded-3 p-3" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4) !important; color: #fca5a5;">
                            <span class="fs-4">⚠️</span>
                            <div>
                                <h6 class="fw-bold mb-1 text-danger">Falha na Execução do Código:</h6>
                                <div class="font-monospace small text-break"><?= htmlspecialchars($erroExecucao) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Card de Telemetria / Performance -->
                    <div class="hud-telemetry p-3 my-3 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; color: #8b949e; font-size: 0.72rem;">TELEMETRIA LINQ:</span>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.45);">
                                ⏱️ <?= $linq->elapsedTime ?>s
                            </span>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(0, 163, 255, 0.18); color: #7dd3fc; border: 1px solid rgba(0, 163, 255, 0.35);">
                                💾 <?= $linq->memoryUsed ?>
                            </span>
                        </div>
                        <div>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(255, 122, 0, 0.2); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.4); font-size: 0.85rem;">
                                📝 <?= $linq->count ?> <?= $linq->count === 1 ? 'linha gerada' : 'linhas geradas' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Bloco de Código Editável (Sandbox) -->
                    <form method="POST" action="?cenario=<?= urlencode($cenarioAtual) ?>" id="formEditor" class="mt-2 mb-4">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: #0b0f19; border: 1px solid #30363d !important;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3" style="background-color: #161b22; border-bottom: 1px solid #30363d;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.05em;">💻 Pipeline LINQ Editável</span>
                                    <span class="small d-none d-sm-inline" style="color: #8b949e;">(PHP 8.1+)</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isCustomCode): ?>
                                        <a href="?cenario=<?= urlencode($cenarioAtual) ?>" class="btn btn-sm btn-nav-action py-1 px-2" style="font-size: 0.78rem;">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrão
                                        </a>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-sm btn-gradient-run py-1 px-3 d-flex align-items-center gap-1" style="font-size: 0.82rem;">
                                        <i class="bi bi-play-fill fs-6"></i>
                                        <span>Simular / Executar</span>
                                        <span class="d-none d-md-inline opacity-75 small">(Ctrl+Enter)</span>
                                    </button>
                                </div>
                            </div>
                            <div class="p-2">
                                <textarea name="codigo_customizado" id="codigoEditor" class="form-control font-monospace border-0 bg-transparent p-2" rows="8" spellcheck="false" style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 0.88rem; line-height: 1.5; resize: vertical; outline: none; box-shadow: none; color: #38bdf8 !important;"><?= htmlspecialchars($codigoAtivo) ?></textarea>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3" style="background-color: #0b0f19; border-top: 1px solid #21262d; font-size: 0.75rem;">
                                <span style="color: #8b949e;">
                                    <strong class="text-light">Variáveis disponíveis:</strong> <code class="text-info">$vendas</code>, <code class="text-info">$produtos</code>, <code class="text-info">$produtosOutlet</code>, <code class="text-info">$clientes</code> | <code class="text-warning">JoinType::*</code>, <code class="text-warning">DB::query()</code> (tabela <code class="text-info">vendas_sql</code>)
                                </span>
                                <span class="d-none d-md-inline" style="color: #6e7681;">💡 Use Tab para indentar e Ctrl+Enter para executar</span>
                            </div>
                        </div>
                    </form>

                    <!-- Tabela de Saída -->
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-uppercase mb-0" style="font-size: 0.78rem; letter-spacing: 0.05em; color: #8b949e;">
                                🎯 Resultado Projetado
                            </h6>
                            <span class="badge rounded-pill" style="background: #21262d; border: 1px solid #30363d; color: #8b949e;">
                                <?= count($resultado) ?> linhas retornadas
                            </span>
                        </div>
                        <?= renderGrid('Dados Processados', $resultado, 'bg-dark', $linq->count) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <footer class="text-center py-4 small mt-5" style="background-color: #161b22; border-top: 1px solid #30363d; color: #8b949e;">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                ⚡ <strong>LinqPHP</strong> © <?= date('Y') ?> Heliomar P. Marques — Zero dependências em produção.
            </span>
            <div class="d-flex align-items-center gap-3">
                <a href="https://github.com/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: #8b949e;">
                    <i class="bi bi-github me-1"></i> Repositório GitHub
                </a>
                <span>•</span>
                <a href="https://packagist.org/packages/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: #8b949e;">
                    <i class="bi bi-box-seam me-1"></i> Packagist
                </a>
                <span>•</span>
                <a href="https://github.com/heliomarpm/linq-php/blob/main/LICENSE" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: #8b949e;">
                    Licença MIT
                </a>
            </div>
        </div>
    </footer>

    <script>
        const editor = document.getElementById('codigoEditor');
        const form = document.getElementById('formEditor');

        if (editor && form) {
            // Suporte para indentação com tecla TAB e envio com Ctrl+Enter
            editor.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                    this.selectionStart = this.selectionEnd = start + 4;
                } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>
