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
            return "<div class='alert alert-warning py-2 mb-3' style='font-size: 0.85rem;'>Nenhum registro encontrado.</div>";
        }

        $colunas = array_keys($dados[0]);
        $badgeCount = $totalRegistros ?? count($dados);

        $html = "<div class='card shadow-sm mb-3 border-0 rounded-3 overflow-hidden'>";
        $html .= "<div class='card-header text-white {$corHeader} d-flex justify-content-between align-items-center py-2 px-3'>";
        $html .= "<span class='fw-semibold' style='font-size: 0.85rem;'>{$titulo}</span>";
        $html .= "<span class='badge bg-light text-dark rounded-pill' style='font-size: 0.72rem;'>{$badgeCount} itens</span>";
        $html .= "</div>";
        $html .= "<div class='card-body p-0'><div class='table-responsive'><table class='table table-sm table-striped table-hover mb-0 align-middle' style='font-size: 0.82rem;'>";
        $html .= "<thead class='table-light'><tr>";

        foreach ($colunas as $col) {
            $html .= "<th class='text-secondary py-2 px-2' style='font-weight: 600; font-size: 0.75rem;'>" . strtoupper($col) . "</th>";
        }

        $html .= "</tr></thead><tbody>";

        foreach ($dados as $linha) {
            $html .= "<tr>";
            foreach ($colunas as $col) {
                $valor = $linha[$col] ?? null;

                if ($valor === null) {
                    $celula = '<span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.7rem;">null</span>';
                } elseif (is_bool($valor)) {
                    $celula = $valor
                        ? '<span class="badge bg-success-subtle text-success">Sim</span>'
                        : '<span class="badge bg-light text-muted border">Não</span>';
                } elseif (is_float($valor)) {
                    $celula = number_format($valor, 2, ',', '.');
                } elseif (is_array($valor)) {
                    $celula = '<code class="small">' . htmlspecialchars(json_encode($valor, JSON_UNESCAPED_UNICODE)) . '</code>';
                } elseif ($col === 'status') {
                    $corStatus = match ($valor) {
                        'concluido' => 'bg-success text-white',
                        'pendente'  => 'bg-warning text-dark',
                        'cancelado' => 'bg-danger text-white',
                        default     => 'bg-secondary text-white'
                    };
                    $celula = "<span class='badge rounded-pill {$corStatus} text-uppercase' style='font-size: 0.68rem;'>{$valor}</span>";
                } else {
                    $celula = htmlspecialchars((string)$valor);
                }

                $html .= "<td class='py-2 px-2'>{$celula}</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table></div></div></div>";
        return $html;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinqPHP - Laboratório Interativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --brand-purple: #6f42c1;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding-bottom: 60px;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-header {
            background: var(--primary-gradient);
        }

        .nav-pills .nav-link {
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            padding: 8px 14px;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #e2e8f0;
            transform: translateY(-1px);
        }

        .nav-pills .nav-link.active {
            background: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        .data-sources {
            max-height: 82vh;
            overflow-y: auto;
            padding-right: 8px;
        }

        .data-sources::-webkit-scrollbar {
            width: 5px;
        }

        .data-sources::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }

        .code-container {
            font-family: 'JetBrains Mono', Consolas, monospace;
            background-color: #0f172a;
            border-radius: 10px;
            font-size: 0.88rem;
        }

        .performance-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 10px;
        }

        .tag-pill {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark brand-header mb-4 shadow-sm py-3">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
                    <span>⚡</span>
                    <span>LinqPHP</span>
                    <span class="badge bg-white text-dark rounded-pill fw-semibold" style="font-size: 0.7rem;">v2.1.0</span>
                </span>
                <span class="text-white-50 small d-none d-lg-inline">Laboratório Interativo & Benchmark DX</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="https://github.com/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light d-flex align-items-center gap-2 fw-semibold px-3 py-2 shadow-sm rounded-pill text-dark" style="font-size: 0.82rem; transition: transform 0.2s ease;">
                    <svg height="16" width="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                    </svg>
                    <span>Repositório GitHub</span>
                </a>
                <a href="https://packagist.org/packages/heliomarpm/linq-php" target="_blank" class="btn btn-sm btn-outline-warning px-3">
                    <i class="bi bi-box-seam me-1"></i> Packagist
                </a>
                <span>&nbsp;&nbsp;</span>
                <a href="https://db-php.vercel.app/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info d-flex align-items-center gap-2 fw-semibold px-3 py-2 shadow-sm rounded-pill text-white" style="font-size: 0.82rem; border-color: rgba(255,255,255,0.4); text-decoration: none;" title="Acesse a demonstração da biblioteca parceira DbPHP">
                    <span>🗄️</span>
                    <span>DbPHP Demo</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Barra de Seleção de Cenários -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body bg-white rounded-3 p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Cenários de Demonstração</span>
                            <span class="badge bg-light text-secondary border"><?= count($cenarios) ?> cenários disponíveis</span>
                        </div>
                        <ul class="nav nav-pills gap-1">
                            <?php foreach ($cenarios as $key => $cenario): ?>
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center gap-2 <?= $key === $cenarioAtual ? 'active' : 'bg-light text-dark border' ?>"
                                        href="?cenario=<?= $key ?>">
                                        <span class="badge bg-<?= $cenario['cor'] ?> tag-pill"><?= $cenario['tag'] ?></span>
                                        <span><?= $cenario['titulo'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna da Esquerda: Coleções de Origem (Com Scroll Suave) -->
            <div class="col-lg-4 data-sources">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary fw-bold text-uppercase mb-0" style="letter-spacing: 0.05em;">
                        🗄️ Coleções & Tabelas
                    </h6>
                    <span class="text-muted small">4 Origens + SQLite</span>
                </div>

                <?= renderGrid('Clientes', $clientes, 'bg-indigo', count($clientes)) ?>
                <?= renderGrid('Produtos', $produtos, 'bg-dark', count($produtos)) ?>
                <?= renderGrid('Produtos Outlet', $produtosOutlet, 'bg-secondary', count($produtosOutlet)) ?>
                <?= renderGrid('Vendas (Array)', $vendas, 'bg-primary', count($vendas)) ?>

                <?php if (class_exists(DB::class)): ?>
                    <div class="card shadow-sm border-0 rounded-3 mb-3" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-warning text-dark tag-pill">SQLite :memory:</span>
                                <span class="text-white-50 small">DbPHP Nativo</span>
                            </div>
                            <div class="fw-bold mb-1" style="font-size: 0.88rem;">
                                <span>🗄️ Tabela <code>vendas_sql</code></span>
                            </div>
                            <div class="small text-secondary mb-2" style="font-size: 0.78rem;">
                                Conectada via <code>DB::sqlite()</code>. Pronta para consultas SQL diretas com <code>DB::query()</code> no editor.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="https://db-php.vercel.app/" target="_blank" class="btn btn-xs btn-outline-warning rounded-pill py-1 px-2" style="font-size: 0.72rem; text-decoration: none;">
                                    Demo DbPHP ↗
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna da Direita: Resultado do Cenário Selecionado -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-<?= $dadosCenario['cor'] ?> tag-pill"><?= $dadosCenario['tag'] ?></span>
                                <?php if ($isCustomCode): ?>
                                    <span class="badge bg-warning text-dark tag-pill">⚡ Código Editado</span>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold text-dark mb-1"><?= $dadosCenario['titulo'] ?></h4>
                            <p class="text-muted mb-0"><?= $dadosCenario['desc'] ?></p>
                        </div>
                    </div>

                    <?php if ($cenarioAtual === 'do_banco_ao_relatorio'): ?>
                        <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 my-3 rounded-3" style="background: linear-gradient(135deg, rgba(254, 243, 199, 0.9) 0%, rgba(253, 230, 138, 0.9) 100%);">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-4">🗄️</span>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">Consulta SQL Real com heliomarpm/db-php</div>
                                    <div class="small text-secondary">
                                        Este cenário executa <code>DB::query(...)</code> em tabela SQLite real em memória (<code>vendas_sql</code>) com parâmetros nomeados (<code>:status</code>) e encadeia direto no LinqPHP via <code>-&gt;linq()</code> para enriquecimento relacional.
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                                <a href="https://db-php.vercel.app/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-dark px-3 py-2 fw-semibold rounded-pill d-flex align-items-center gap-1 text-nowrap" style="font-size: 0.8rem;">
                                    <span>Demo DbPHP</span>
                                    <span>↗</span>
                                </a>
                                <a href="https://github.com/heliomarpm/db-php" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark px-2 py-2 fw-semibold rounded-pill d-flex align-items-center gap-1 text-nowrap" style="font-size: 0.8rem;">
                                    <span>GitHub</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Mensagem de Erro (se houver) -->
                    <?php if ($erroExecucao !== null): ?>
                        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-start gap-3 my-3 rounded-3 p-3">
                            <span class="fs-4">⚠️</span>
                            <div>
                                <h6 class="fw-bold mb-1">Falha na Execução do Código:</h6>
                                <div class="font-monospace small text-break"><?= htmlspecialchars($erroExecucao) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Card de Telemetria / Performance -->
                    <div class="performance-card text-white p-3 my-3 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-white-50 small fw-semibold">TELEMETRIA LINQ:</span>
                            <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold">
                                ⏱️ <?= $linq->elapsedTime ?>s
                            </span>
                            <span class="badge bg-info text-dark rounded-pill px-3 py-2 fw-semibold">
                                💾 <?= $linq->memoryUsed ?>
                            </span>
                        </div>
                        <div>
                            <span class="badge bg-success rounded-pill px-3 py-2 fw-semibold fs-6">
                                📝 <?= $linq->count ?> <?= $linq->count === 1 ? 'linha gerada' : 'linhas geradas' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Bloco de Código Editável (Sandbox) -->
                    <form method="POST" action="?cenario=<?= urlencode($cenarioAtual) ?>" id="formEditor" class="mt-2 mb-4">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: #0f172a;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3 border-bottom border-secondary-subtle" style="background-color: #1e293b;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.05em;">💻 Pipeline LINQ Editável</span>
                                    <span class="text-white-50 small d-none d-sm-inline">(PHP 8.1+)</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isCustomCode): ?>
                                        <a href="?cenario=<?= urlencode($cenarioAtual) ?>" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 0.78rem;">
                                            🔄 Restaurar Padrão
                                        </a>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-sm btn-primary py-1 px-3 shadow-sm fw-semibold d-flex align-items-center gap-1" style="font-size: 0.8rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none;">
                                        <span>▶️ Simular / Executar</span>
                                        <span class="d-none d-md-inline text-white-50 small">(Ctrl+Enter)</span>
                                    </button>
                                </div>
                            </div>
                            <div class="p-2">
                                <textarea name="codigo_customizado" id="codigoEditor" class="form-control font-monospace border-0 bg-transparent text-info p-2" rows="8" spellcheck="false" style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 0.88rem; line-height: 1.5; resize: vertical; outline: none; box-shadow: none; color: #38bdf8 !important;"><?= htmlspecialchars($codigoAtivo) ?></textarea>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3 border-top border-secondary-subtle" style="background-color: #0b1120; font-size: 0.75rem;">
                                <span class="text-secondary">
                                    <strong class="text-light">Variáveis disponíveis:</strong> <code class="text-info">$vendas</code>, <code class="text-info">$produtos</code>, <code class="text-info">$produtosOutlet</code>, <code class="text-info">$clientes</code> | <code class="text-warning">JoinType::*</code>, <code class="text-warning">DB::query()</code> (tabela <code class="text-info">vendas_sql</code>)
                                </span>
                                <span class="text-white-50 d-none d-md-inline">💡 Use Tab para indentar e Ctrl+Enter para executar</span>
                            </div>
                        </div>
                    </form>

                    <!-- Tabela de Saída -->
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-secondary text-uppercase mb-0" style="font-size: 0.78rem; letter-spacing: 0.05em;">
                                🎯 Resultado Projetado
                            </h6>
                            <span class="badge bg-light text-muted border"><?= count($resultado) ?> linhas retornadas</span>
                        </div>
                        <?= renderGrid('Dados Processados', $resultado, 'bg-dark', $linq->count) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <footer class="text-center py-4 text-muted small border-top bg-white mt-5">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                ⚡ <strong>LinqPHP</strong> © <?= date('Y') ?> Heliomar P. Marques — Zero dependências.
            </span>
            <div class="d-flex align-items-center gap-3">
                <a href="https://github.com/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-secondary">
                    Repositório GitHub
                </a>
                <span>•</span>
                <a href="https://packagist.org/packages/heliomarpm/linq-php" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-secondary">
                    Packagist
                </a>
                <span>•</span>
                <a href="https://github.com/heliomarpm/linq-php/blob/main/LICENSE" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-secondary">
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
