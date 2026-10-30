<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db_fallback.php';
require_once __DIR__ . '/sandbox_validator.php';
require_once __DIR__ . '/BenchmarkMetrics.php';

// Servir Favicon estático imediatamente para /favicon.ico ou /favicon.svg
if (in_array(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), ['/favicon.ico', '/favicon.svg'], true)) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=604800, immutable');
    readfile(__DIR__ . '/favicon.svg');
    exit;
}

use HeliomarPM\DbPHP\DB;
use HeliomarPM\LinqPHP\Demo\BenchmarkMetrics;
use HeliomarPM\LinqPHP\Demo\CodeSandboxValidator;
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

    DB::execute('CREATE TABLE IF NOT EXISTS vendas_sql (
        id_venda INTEGER PRIMARY KEY,
        produto_id INTEGER,
        cliente_id INTEGER,
        qtd INTEGER,
        canal TEXT,
        status TEXT
    )');

    foreach ($vendas as $v) {
        DB::execute(
            'INSERT INTO vendas_sql (id_venda, produto_id, cliente_id, qtd, canal, status) VALUES (?, ?, ?, ?, ?, ?)',
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
    ],

    'paginacao' => [
        'titulo' => 'Paginação (Skip + Take)',
        'tag'    => 'PAGINATION',
        'cor'    => 'primary',
        'desc'   => 'Ordena as vendas pelo identificador e retorna a segunda página, ignorando os cinco primeiros registros e limitando o resultado a cinco itens.',
        'codigo' => <<<PHP_WRAP
        \$linq = LinqPHP::from(\$vendas)
            ->orderByKey('id_venda')
            ->skip(5)
            ->take(5)
            ->toObject();
        PHP_WRAP,
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
    ],

    'primeiro_registro_first' => [
        'titulo' => 'Primeiro Registro (first)',
        'tag'    => 'FIRST',
        'cor'    => 'info',
        'desc'   => 'Retorna o primeiro cliente VIP de Minas Gerais e lança UnderflowException se nenhum registro corresponder.',
        'codigo' => <<<PHP_WRAP
        \$item = LinqPHP::from(\$clientes)
            ->first(fn(\$c) => \$c['uf'] === 'MG' && \$c['vip'] === true);
        PHP_WRAP,
    ],

    'ultimo_registro' => [
        'titulo' => 'Último Registro (last + lastOrDefault)',
        'tag'    => 'LAST',
        'cor'    => 'info',
        'desc'   => 'Localiza o último cliente VIP de Minas Gerais com last(); lastOrDefault() pode retornar um valor alternativo se não houver correspondência.',
        'codigo' => <<<PHP_WRAP
        \$item = LinqPHP::from(\$clientes)
            ->last(fn(\$c) => \$c['uf'] === 'MG' && \$c['vip'] === true);
        PHP_WRAP,
    ],

    'any_all' => [
        'titulo' => 'Predicados (any + all)',
        'tag'    => 'ANY & ALL',
        'cor'    => 'success',
        'desc'   => 'Verifica em curto-circuito se existe uma venda concluída e se todas as vendas possuem identificador positivo.',
        'codigo' => <<<PHP_WRAP
        \$haVendasConcluidas = LinqPHP::from(\$vendas)
            ->any(fn(\$v) => \$v['status'] === 'concluido');
        \$todasTemIdPositivo = LinqPHP::from(\$vendas)
            ->all(fn(\$v) => \$v['id_venda'] > 0);
        \$resultado = [
            ['Verificação' => 'Existe venda concluída', 'Resultado' => \$haVendasConcluidas],
            ['Verificação' => 'Todas as vendas têm ID positivo', 'Resultado' => \$todasTemIdPositivo],
        ];
        PHP_WRAP,
    ],

    'pluck_produtos' => [
        'titulo' => 'Projeção de Coluna (pluck)',
        'tag'    => 'PLUCK',
        'cor'    => 'info',
        'desc'   => 'Extrai os nomes dos produtos e usa seus IDs como chaves do resultado, sem alterar a coleção original.',
        'codigo' => <<<PHP_WRAP
        \$nomesPorId = LinqPHP::from(\$produtos)
            ->pluck('nome_produto', 'id_produto');
        \$resultado = [\$nomesPorId];
        PHP_WRAP,
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
    ],

    'agregacoes_escalares' => [
        'titulo' => 'Agregações Escalares Rápidas (sum, avg, min, max, count)',
        'tag'    => 'SCALAR AGG',
        'cor'    => 'success',
        'desc'   => 'Extração direta e ultrarrápida de métricas numéricas sem overhead de agrupamento: Total faturado, Ticket Médio, Menor e Maior valor.',
        'codigo' => <<<PHP_WRAP
        \$concluidas = LinqPHP::from(\$vendas)->where(['status', '=', 'concluido']);

        \$totalItens   = \$concluidas->sum('qtd');
        \$totalVendas  = \$concluidas->count();
        \$faturamento  = LinqPHP::from(\$vendas)
            ->where(['status', '=', 'concluido'])
            ->join(\$produtos, JoinType::INNER, ['produto_id' => 'id_produto'])
            ->sum(fn(\$v) => \$v['qtd'] * \$v['preco']);
        \$ticketMedio  = round(\$faturamento / (\$totalVendas ?: 1), 2);
        \$maiorEstoque = LinqPHP::from(\$produtos)->max('estoque');
        \$menorPreco   = LinqPHP::from(\$produtos)->min('preco');
        \$resultado = [
            ['Métrica' => 'Total de Vendas Concluídas', 'Valor' => \$totalVendas, 'Método' => 'count()'],
            ['Métrica' => 'Quantidade Total de Itens Vendidos', 'Valor' => \$totalItens, 'Método' => 'sum("qtd")'],
            ['Métrica' => 'Faturamento Bruto', 'Valor' => 'R$ ' . number_format(\$faturamento, 2, ',', '.'), 'Método' => 'sum(closure)'],
            ['Métrica' => 'Ticket Médio por Venda', 'Valor' => 'R$ ' . number_format(\$ticketMedio, 2, ',', '.'), 'Método' => 'avg()'],
            ['Métrica' => 'Menor Preço de Produto', 'Valor' => 'R$ ' . number_format((float)\$menorPreco, 2, ',', '.'), 'Método' => 'min("preco")'],
            ['Métrica' => 'Maior Estoque Individual', 'Valor' => \$maiorEstoque . ' un', 'Método' => 'max("estoque")'],
        ];
        PHP_WRAP,
    ],
];

$cenarioDbPhp = $cenarios['do_banco_ao_relatorio'];
unset($cenarios['do_banco_ao_relatorio']);
$cenarios['do_banco_ao_relatorio'] = $cenarioDbPhp;

// Suporte a payload JSON no corpo da requisição (Fetch API / Vue 3)
$rawInput = file_get_contents('php://input');
if ($rawInput !== false && $rawInput !== '') {
    $jsonInput = json_decode($rawInput, true);
    if (is_array($jsonInput)) {
        if (!empty($jsonInput['cenario']) && is_string($jsonInput['cenario'])) {
            $_GET['cenario'] = $jsonInput['cenario'];
        }
        if (isset($jsonInput['codigo']) && is_string($jsonInput['codigo'])) {
            $_POST['codigo_customizado'] = $jsonInput['codigo'];
        }
    }
}

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

// Compila o código apresentado no editor em um pipeline reutilizável.
$compilarLinq = function (string $rawCode) use ($clientes, $produtos, $produtosOutlet, $vendas): Closure {
    CodeSandboxValidator::assertSafe($rawCode);

    $cleanCode = trim($rawCode);
    $prefix = "use HeliomarPM\\LinqPHP\\LinqPHP;\nuse HeliomarPM\\LinqPHP\\JoinType;\nuse HeliomarPM\\DbPHP\\DB;\n";
    if (preg_match('/^(LinqPHP::|\(?new\s+LinqPHP|DB::|\(?new\s+DB)/i', $cleanCode)) {
        $cleanCode = 'return ' . rtrim($cleanCode, ';') . ';';
    } else {
        $cleanCode .= "\nreturn \$item ?? \$linq ?? \$resultado ?? \$result ?? \$dbResult ?? \$dados ?? null;";
    }

    $runner = eval(
        $prefix
        . "return static function () use (\$clientes, \$produtos, \$produtosOutlet, \$vendas) {\n"
        . $cleanCode
        . "\n};"
    );

    if (!$runner instanceof Closure) {
        throw new RuntimeException('Não foi possível compilar o pipeline do cenário.');
    }

    return $runner;
};

// Executa um pipeline compilado e normaliza sua saída para a API da demo.
$avaliarLinq = function (Closure $runner): stdClass {
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    $evalResult = $runner();
    $endTime = microtime(true);
    $elapsedTime = number_format($endTime - $startTime, 6);
    $memoryUsed = sprintf('%5.2f MB', (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024);
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

// ==========================================
// 2.1. IMPLEMENTAÇÕES NATIVAS EQUIVALENTES (BENCHMARK)
// ==========================================
$obterNativoCenario = function (string $key, array $vendas, array $produtos, array $produtosOutlet, array $clientes): array {
    switch ($key) {
        case 'join_triplo':
            return [
                'codigo' => <<<'PHP'
// Indexação O(1) de produtos e clientes
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }
$cliMap = [];
foreach ($clientes as $c) { $cliMap[$c['id_cliente']] = $c; }

$resultado = [];
foreach ($vendas as $v) {
    if ($v['status'] !== 'concluido') continue;
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p) continue;
    $c = $cliMap[$v['cliente_id']] ?? null;
    if (!$c || !$c['vip']) continue;
    $resultado[] = [
        'Código Venda'   => $v['id_venda'],
        'Cliente VIP'    => $c['nome_cliente'],
        'uf'             => $c['uf'],
        'Produto'        => $p['nome_produto'],
        'Quantidade'     => $v['qtd'],
        'Valor Unitário' => $p['preco']
    ];
}
usort($resultado, fn($a, $b) => $a['Código Venda'] <=> $b['Código Venda']);
PHP,
                'run' => function () use ($vendas, $produtos, $clientes) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $cliMap = [];
                    foreach ($clientes as $c) {
                        $cliMap[$c['id_cliente']] = $c;
                    }

                    $resultado = [];
                    foreach ($vendas as $v) {
                        if ($v['status'] !== 'concluido') {
                            continue;
                        }
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p) {
                            continue;
                        }
                        $c = $cliMap[$v['cliente_id']] ?? null;
                        if (!$c || !$c['vip']) {
                            continue;
                        }
                        $resultado[] = [
                            'Código Venda'   => $v['id_venda'],
                            'Cliente VIP'    => $c['nome_cliente'],
                            'uf'             => $c['uf'],
                            'Produto'        => $p['nome_produto'],
                            'Quantidade'     => $v['qtd'],
                            'Valor Unitário' => $p['preco'],
                        ];
                    }
                    usort($resultado, fn($a, $b) => $a['Código Venda'] <=> $b['Código Venda']);
                    return $resultado;
                },
            ];

        case 'todas_agregacoes':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }
$grupos = [];
foreach ($vendas as $v) {
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p) continue;
    $cat = $p['categoria'];
    if (!isset($grupos[$cat])) {
        $grupos[$cat] = [
            'categoria'    => $cat,
            'qtd'          => 0,
            'id_venda'     => 0,
            'preco_(max)'  => -INF,
            'preco_(min)'  => INF,
            '_sum_preco'   => 0.0,
            '_count_preco' => 0
        ];
    }
    $grupos[$cat]['qtd'] += (float)$v['qtd'];
    $grupos[$cat]['id_venda']++;
    $grupos[$cat]['preco_(max)'] = max($grupos[$cat]['preco_(max)'], $p['preco']);
    $grupos[$cat]['preco_(min)'] = min($grupos[$cat]['preco_(min)'], $p['preco']);
    $grupos[$cat]['_sum_preco'] += (float)$p['preco'];
    $grupos[$cat]['_count_preco']++;
}
$resultado = [];
foreach ($grupos as $g) {
    $resultado[] = [
        'categoria'   => $g['categoria'],
        'qtd'         => $g['qtd'],
        'id_venda'    => $g['id_venda'],
        'preco_(max)' => $g['preco_(max)'],
        'preco_(min)' => $g['preco_(min)'],
        'preco_(avg)' => $g['_count_preco'] > 0 ? $g['_sum_preco'] / $g['_count_preco'] : null
    ];
}
PHP,
                'run' => function () use ($vendas, $produtos) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $grupos = [];
                    foreach ($vendas as $v) {
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p) {
                            continue;
                        }
                        $cat = $p['categoria'];
                        if (!isset($grupos[$cat])) {
                            $grupos[$cat] = [
                                'categoria'    => $cat,
                                'qtd'          => 0,
                                'id_venda'     => 0,
                                'preco_(max)'  => -INF,
                                'preco_(min)'  => INF,
                                '_sum_preco'   => 0.0,
                                '_count_preco' => 0,
                            ];
                        }
                        $grupos[$cat]['qtd'] += (float)$v['qtd'];
                        $grupos[$cat]['id_venda']++;
                        $grupos[$cat]['preco_(max)'] = max($grupos[$cat]['preco_(max)'], $p['preco']);
                        $grupos[$cat]['preco_(min)'] = min($grupos[$cat]['preco_(min)'], $p['preco']);
                        $grupos[$cat]['_sum_preco'] += (float)$p['preco'];
                        $grupos[$cat]['_count_preco']++;
                    }
                    $resultado = [];
                    foreach ($grupos as $g) {
                        $resultado[] = [
                            'categoria'   => $g['categoria'],
                            'qtd'         => $g['qtd'],
                            'id_venda'    => $g['id_venda'],
                            'preco_(max)' => $g['preco_(max)'],
                            'preco_(min)' => $g['preco_(min)'],
                            'preco_(avg)' => $g['_count_preco'] > 0 ? $g['_sum_preco'] / $g['_count_preco'] : null,
                        ];
                    }
                    return $resultado;
                },
            ];

        case 'totalizador_global':
            return [
                'codigo' => <<<'PHP'
$totalQtd = 0;
$totalVendas = 0;
foreach ($vendas as $v) {
    if ($v['status'] === 'concluido') {
        $totalQtd += (float)$v['qtd'];
        $totalVendas++;
    }
}
$resultado = [['qtd' => $totalQtd, 'total_vendas' => $totalVendas]];
PHP,
                'run' => function () use ($vendas) {
                    $totalQtd = 0;
                    $totalVendas = 0;
                    foreach ($vendas as $v) {
                        if ($v['status'] === 'concluido') {
                            $totalQtd += (float)$v['qtd'];
                            $totalVendas++;
                        }
                    }
                    return [['qtd' => $totalQtd, 'total_vendas' => $totalVendas]];
                },
            ];

        case 'top_vendas':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }

$resultado = [];
foreach ($vendas as $v) {
    if ($v['status'] !== 'concluido') continue;
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p) continue;
    $resultado[] = [
        'id_venda'     => $v['id_venda'],
        'nome_produto' => $p['nome_produto'],
        'categoria'    => $p['categoria'],
        'qtd'          => $v['qtd'],
        'preco'        => $p['preco']
    ];
}
usort($resultado, fn($a, $b) => $b['qtd'] <=> $a['qtd']);
$resultado = array_slice($resultado, 0, 4);
PHP,
                'run' => function () use ($vendas, $produtos) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $resultado = [];
                    foreach ($vendas as $v) {
                        if ($v['status'] !== 'concluido') {
                            continue;
                        }
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p) {
                            continue;
                        }
                        $resultado[] = [
                            'id_venda'     => $v['id_venda'],
                            'nome_produto' => $p['nome_produto'],
                            'categoria'    => $p['categoria'],
                            'qtd'          => $v['qtd'],
                            'preco'        => $p['preco'],
                        ];
                    }
                    usort($resultado, fn($a, $b) => $b['qtd'] <=> $a['qtd']);
                    return array_slice($resultado, 0, 4);
                },
            ];

        case 'ordenacao_multipla':
            return [
                'codigo' => <<<'PHP'
$resultado = [];
foreach ($produtos as $p) {
    $resultado[] = [
        'id_produto'   => $p['id_produto'],
        'nome_produto' => $p['nome_produto'],
        'categoria'    => $p['categoria'],
        'preco'        => $p['preco'],
        'estoque'      => $p['estoque']
    ];
}
usort($resultado, function ($a, $b) {
    $cmpCat = strcmp($a['categoria'], $b['categoria']);
    if ($cmpCat !== 0) return $cmpCat;
    return $b['preco'] <=> $a['preco'];
});
PHP,
                'run' => function () use ($produtos) {
                    $resultado = [];
                    foreach ($produtos as $p) {
                        $resultado[] = [
                            'id_produto'   => $p['id_produto'],
                            'nome_produto' => $p['nome_produto'],
                            'categoria'    => $p['categoria'],
                            'preco'        => $p['preco'],
                            'estoque'      => $p['estoque'],
                        ];
                    }
                    usort($resultado, function ($a, $b) {
                        $cmpCat = strcmp($a['categoria'], $b['categoria']);
                        if ($cmpCat !== 0) {
                            return $cmpCat;
                        }
                        return $b['preco'] <=> $a['preco'];
                    });
                    return $resultado;
                },
            ];

        case 'ordenacao_closure':
            return [
                'codigo' => <<<'PHP'
$resultado = [];
foreach ($produtos as $p) {
    $resultado[] = [
        'id_produto'   => $p['id_produto'],
        'nome_produto' => $p['nome_produto'],
        'categoria'    => $p['categoria'],
        'preco'        => $p['preco'],
        'estoque'      => $p['estoque']
    ];
}
usort($resultado, function ($a, $b) {
    $valA = $a['preco'] * $a['estoque'];
    $valB = $b['preco'] * $b['estoque'];
    return $valB <=> $valA;
});
PHP,
                'run' => function () use ($produtos) {
                    $resultado = [];
                    foreach ($produtos as $p) {
                        $resultado[] = [
                            'id_produto'   => $p['id_produto'],
                            'nome_produto' => $p['nome_produto'],
                            'categoria'    => $p['categoria'],
                            'preco'        => $p['preco'],
                            'estoque'      => $p['estoque'],
                        ];
                    }
                    usort($resultado, function ($a, $b) {
                        $valA = $a['preco'] * $a['estoque'];
                        $valB = $b['preco'] * $b['estoque'];
                        return $valB <=> $valA;
                    });
                    return $resultado;
                },
            ];

        case 'full_join':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }
$produtosMatched = [];
$resultado = [];

// 1. Linhas de vendas (Inner Join ou órfãos sem produto)
foreach ($vendas as $v) {
    $pid = $v['produto_id'];
    $p = $prodMap[$pid] ?? null;
    if ($p) {
        $produtosMatched[$pid] = true;
        $resultado[] = [
            'id_venda'     => $v['id_venda'],
            'produto_id'   => $v['produto_id'],
            'nome_produto' => $p['nome_produto'],
            'categoria'    => $p['categoria'],
            'qtd'          => $v['qtd'],
            'preco'        => $p['preco']
        ];
    } else {
        $resultado[] = [
            'id_venda'     => $v['id_venda'],
            'produto_id'   => $v['produto_id'],
            'nome_produto' => null,
            'categoria'    => null,
            'qtd'          => $v['qtd'],
            'preco'        => null
        ];
    }
}

// 2. Produtos do catálogo sem vendas (Right-only)
foreach ($produtos as $p) {
    if (!isset($produtosMatched[$p['id_produto']])) {
        $resultado[] = [
            'id_venda'     => null,
            'produto_id'   => null,
            'nome_produto' => $p['nome_produto'],
            'categoria'    => $p['categoria'],
            'qtd'          => null,
            'preco'        => $p['preco']
        ];
    }
}
PHP,
                'run' => function () use ($vendas, $produtos) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $produtosMatched = [];
                    $resultado = [];

                    // 1. Linhas de vendas (Inner Join ou órfãos sem produto)
                    foreach ($vendas as $v) {
                        $pid = $v['produto_id'];
                        $p = $prodMap[$pid] ?? null;
                        if ($p) {
                            $produtosMatched[$pid] = true;
                            $resultado[] = [
                                'id_venda'     => $v['id_venda'],
                                'produto_id'   => $v['produto_id'],
                                'nome_produto' => $p['nome_produto'],
                                'categoria'    => $p['categoria'],
                                'qtd'          => $v['qtd'],
                                'preco'        => $p['preco'],
                            ];
                        } else {
                            $resultado[] = [
                                'id_venda'     => $v['id_venda'],
                                'produto_id'   => $v['produto_id'],
                                'nome_produto' => null,
                                'categoria'    => null,
                                'qtd'          => $v['qtd'],
                                'preco'        => null,
                            ];
                        }
                    }

                    // 2. Produtos do catálogo sem vendas (Right-only)
                    foreach ($produtos as $p) {
                        if (!isset($produtosMatched[$p['id_produto']])) {
                            $resultado[] = [
                                'id_venda'     => null,
                                'produto_id'   => null,
                                'nome_produto' => $p['nome_produto'],
                                'categoria'    => $p['categoria'],
                                'qtd'          => null,
                                'preco'        => $p['preco'],
                            ];
                        }
                    }
                    return $resultado;
                },
            ];

        case 'right_join':
            return [
                'codigo' => <<<'PHP'
$cliMap = [];
foreach ($clientes as $c) { $cliMap[$c['id_cliente']] = $c; }
$clientesMatched = [];
$resultado = [];

// 1. Vendas com cliente associado (ordem natural de vendas)
foreach ($vendas as $v) {
    $cid = $v['cliente_id'];
    $c = $cliMap[$cid] ?? null;
    if ($c) {
        $clientesMatched[$cid] = true;
        $resultado[] = [
            'id_cliente'   => $c['id_cliente'],
            'nome_cliente' => $c['nome_cliente'],
            'cidade'       => $c['cidade'],
            'uf'           => $c['uf'],
            'id_venda'     => $v['id_venda'],
            'status'       => $v['status']
        ];
    }
}

// 2. Clientes ociosos que nunca compraram (Right-only)
foreach ($clientes as $c) {
    if (!isset($clientesMatched[$c['id_cliente']])) {
        $resultado[] = [
            'id_cliente'   => $c['id_cliente'],
            'nome_cliente' => $c['nome_cliente'],
            'cidade'       => $c['cidade'],
            'uf'           => $c['uf'],
            'id_venda'     => null,
            'status'       => null
        ];
    }
}
PHP,
                'run' => function () use ($vendas, $clientes) {
                    $cliMap = [];
                    foreach ($clientes as $c) {
                        $cliMap[$c['id_cliente']] = $c;
                    }
                    $clientesMatched = [];
                    $resultado = [];

                    // 1. Vendas com cliente associado
                    foreach ($vendas as $v) {
                        $cid = $v['cliente_id'];
                        $c = $cliMap[$cid] ?? null;
                        if ($c) {
                            $clientesMatched[$cid] = true;
                            $resultado[] = [
                                'id_cliente'   => $c['id_cliente'],
                                'nome_cliente' => $c['nome_cliente'],
                                'cidade'       => $c['cidade'],
                                'uf'           => $c['uf'],
                                'id_venda'     => $v['id_venda'],
                                'status'       => $v['status'],
                            ];
                        }
                    }

                    // 2. Clientes ociosos que nunca compraram (Right-only)
                    foreach ($clientes as $c) {
                        if (!isset($clientesMatched[$c['id_cliente']])) {
                            $resultado[] = [
                                'id_cliente'   => $c['id_cliente'],
                                'nome_cliente' => $c['nome_cliente'],
                                'cidade'       => $c['cidade'],
                                'uf'           => $c['uf'],
                                'id_venda'     => null,
                                'status'       => null,
                            ];
                        }
                    }
                    return $resultado;
                },
            ];

        case 'multi_where':
            return [
                'codigo' => <<<'PHP'
$resultado = [];
foreach ($produtos as $p) {
    if (
        $p['preco'] >= 80.0
        && $p['preco'] <= 150.0
        && $p['categoria'] === 'Insumo'
        && $p['estoque'] > 10
    ) {
        $resultado[] = [
            'nome_produto' => $p['nome_produto'],
            'categoria'    => $p['categoria'],
            'preco'        => $p['preco'],
            'estoque'      => $p['estoque']
        ];
    }
}
usort($resultado, fn($a, $b) => $a['preco'] <=> $b['preco']);
PHP,
                'run' => function () use ($produtos) {
                    $resultado = [];
                    foreach ($produtos as $p) {
                        if (
                            $p['preco'] >= 80.0
                            && $p['preco'] <= 150.0
                            && $p['categoria'] === 'Insumo'
                            && $p['estoque'] > 10
                        ) {
                            $resultado[] = [
                                'nome_produto' => $p['nome_produto'],
                                'categoria'    => $p['categoria'],
                                'preco'        => $p['preco'],
                                'estoque'      => $p['estoque'],
                            ];
                        }
                    }
                    usort($resultado, fn($a, $b) => $a['preco'] <=> $b['preco']);
                    return $resultado;
                },
            ];

        case 'union_outlet':
            return [
                'codigo' => <<<'PHP'
$resultado = [];
foreach ($produtos as $p) {
    $resultado[] = [
        'id_produto'    => $p['id_produto'],
        'nome_produto'  => $p['nome_produto'],
        'categoria'     => $p['categoria'],
        'preco'         => $p['preco'],
        'motivo_outlet' => null
    ];
}
foreach ($produtosOutlet as $po) {
    $resultado[] = [
        'id_produto'    => $po['id_produto'],
        'nome_produto'  => $po['nome_produto'],
        'categoria'     => $po['categoria'],
        'preco'         => $po['preco'],
        'motivo_outlet' => $po['motivo_outlet'] ?? null
    ];
}
usort($resultado, fn($a, $b) => $a['preco'] <=> $b['preco']);
PHP,
                'run' => function () use ($produtos, $produtosOutlet) {
                    $resultado = [];
                    foreach ($produtos as $p) {
                        $resultado[] = [
                            'id_produto'    => $p['id_produto'],
                            'nome_produto'  => $p['nome_produto'],
                            'categoria'     => $p['categoria'],
                            'preco'         => $p['preco'],
                            'motivo_outlet' => null,
                        ];
                    }
                    foreach ($produtosOutlet as $po) {
                        $resultado[] = [
                            'id_produto'    => $po['id_produto'],
                            'nome_produto'  => $po['nome_produto'],
                            'categoria'     => $po['categoria'],
                            'preco'         => $po['preco'],
                            'motivo_outlet' => $po['motivo_outlet'] ?? null,
                        ];
                    }
                    usort($resultado, fn($a, $b) => $a['preco'] <=> $b['preco']);
                    return $resultado;
                },
            ];

        case 'distinct_uf':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }
$cliMap = [];
foreach ($clientes as $c) { $cliMap[$c['id_cliente']] = $c; }

$ufs = [];
foreach ($vendas as $v) {
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p || $p['categoria'] !== 'Peça') continue;
    $c = $cliMap[$v['cliente_id']] ?? null;
    if (!$c) continue;
    $ufs[$c['uf']] = true;
}
$resultado = [];
foreach (array_keys($ufs) as $uf) {
    $resultado[] = ['uf' => $uf];
}
PHP,
                'run' => function () use ($vendas, $produtos, $clientes) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $cliMap = [];
                    foreach ($clientes as $c) {
                        $cliMap[$c['id_cliente']] = $c;
                    }

                    $ufs = [];
                    foreach ($vendas as $v) {
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p || $p['categoria'] !== 'Peça') {
                            continue;
                        }
                        $c = $cliMap[$v['cliente_id']] ?? null;
                        if (!$c) {
                            continue;
                        }
                        $ufs[$c['uf']] = true;
                    }
                    $resultado = [];
                    foreach (array_keys($ufs) as $uf) {
                        $resultado[] = ['uf' => $uf];
                    }
                    return $resultado;
                },
            ];

        case 'complex_closure':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }

$resultado = [];
foreach ($vendas as $v) {
    if ($v['status'] !== 'concluido') continue;
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p || $p['categoria'] !== 'Insumo') continue;
    $faturamento = $v['qtd'] * $p['preco'];
    if ($faturamento > 200.00) {
        $resultado[] = [
            'id_venda'     => $v['id_venda'],
            'nome_produto' => $p['nome_produto'],
            'qtd'          => $v['qtd'],
            'preco'        => $p['preco']
        ];
    }
}
PHP,
                'run' => function () use ($vendas, $produtos) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $resultado = [];
                    foreach ($vendas as $v) {
                        if ($v['status'] !== 'concluido') {
                            continue;
                        }
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p || $p['categoria'] !== 'Insumo') {
                            continue;
                        }
                        $faturamento = $v['qtd'] * $p['preco'];
                        if ($faturamento > 200.00) {
                            $resultado[] = [
                                'id_venda'     => $v['id_venda'],
                                'nome_produto' => $p['nome_produto'],
                                'qtd'          => $v['qtd'],
                                'preco'        => $p['preco'],
                            ];
                        }
                    }
                    return $resultado;
                },
            ];

        case 'primeiro_registro':
            return [
                'codigo' => <<<'PHP'
$encontrado = null;
foreach ($clientes as $c) {
    if ($c['uf'] === 'MG' && $c['vip'] === true) {
        $encontrado = $c;
        break;
    }
}
$resultado = $encontrado ? [$encontrado] : [];
PHP,
                'run' => function () use ($clientes) {
                    $encontrado = null;
                    foreach ($clientes as $c) {
                        if ($c['uf'] === 'MG' && $c['vip'] === true) {
                            $encontrado = $c;
                            break;
                        }
                    }
                    return $encontrado ? [$encontrado] : [];
                },
            ];

        case 'primeiro_registro_first':
            return [
                'codigo' => <<<'PHP'
$encontrado = null;
foreach ($clientes as $c) {
    if ($c['uf'] === 'MG' && $c['vip'] === true) {
        $encontrado = $c;
        break;
    }
}
if ($encontrado === null) {
    throw new UnderflowException('Nenhum cliente correspondente foi encontrado.');
}
$resultado = [$encontrado];
PHP,
                'run' => static function () use ($clientes): array {
                    foreach ($clientes as $cliente) {
                        if ($cliente['uf'] === 'MG' && $cliente['vip'] === true) {
                            return [$cliente];
                        }
                    }

                    throw new UnderflowException('Nenhum cliente correspondente foi encontrado.');
                },
            ];

        case 'ultimo_registro':
            return [
                'codigo' => <<<'PHP'
$ultimo = null;
foreach ($clientes as $c) {
    if ($c['uf'] === 'MG' && $c['vip'] === true) {
        $ultimo = $c;
    }
}
if ($ultimo === null) {
    throw new UnderflowException('Nenhum cliente correspondente foi encontrado.');
}
$resultado = [$ultimo];
PHP,
                'run' => static function () use ($clientes): array {
                    $ultimo = null;
                    foreach ($clientes as $cliente) {
                        if ($cliente['uf'] === 'MG' && $cliente['vip'] === true) {
                            $ultimo = $cliente;
                        }
                    }

                    if ($ultimo === null) {
                        throw new UnderflowException('Nenhum cliente correspondente foi encontrado.');
                    }

                    return [$ultimo];
                },
            ];

        case 'any_all':
            return [
                'codigo' => <<<'PHP'
$haVendasConcluidas = false;
foreach ($vendas as $venda) {
    if ($venda['status'] === 'concluido') {
        $haVendasConcluidas = true;
        break;
    }
}
$todasTemIdPositivo = true;
foreach ($vendas as $venda) {
    if ($venda['id_venda'] <= 0) {
        $todasTemIdPositivo = false;
        break;
    }
}
$resultado = [
    ['Verificação' => 'Existe venda concluída', 'Resultado' => $haVendasConcluidas],
    ['Verificação' => 'Todas as vendas têm ID positivo', 'Resultado' => $todasTemIdPositivo],
];
PHP,
                'run' => static function () use ($vendas): array {
                    $haVendasConcluidas = false;
                    foreach ($vendas as $venda) {
                        if ($venda['status'] === 'concluido') {
                            $haVendasConcluidas = true;
                            break;
                        }
                    }

                    $todasTemIdPositivo = true;
                    foreach ($vendas as $venda) {
                        if ($venda['id_venda'] <= 0) {
                            $todasTemIdPositivo = false;
                            break;
                        }
                    }

                    return [
                        ['Verificação' => 'Existe venda concluída', 'Resultado' => $haVendasConcluidas],
                        ['Verificação' => 'Todas as vendas têm ID positivo', 'Resultado' => $todasTemIdPositivo],
                    ];
                },
            ];

        case 'pluck_produtos':
            return [
                'codigo' => <<<'PHP'
$nomesPorId = array_column($produtos, 'nome_produto', 'id_produto');
$resultado = [$nomesPorId];
PHP,
                'run' => static function () use ($produtos): array {
                    return [array_column($produtos, 'nome_produto', 'id_produto')];
                },
            ];

        case 'do_banco_ao_relatorio':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }
$cliMap = [];
foreach ($clientes as $c) { $cliMap[$c['id_cliente']] = $c; }

$vendasSql = DB::query("
    SELECT id_venda, produto_id, cliente_id, qtd
    FROM vendas_sql
    WHERE status = :status
", ['status' => 'concluido']);

$resultado = [];
foreach ($vendasSql as $v) {
    $p = $prodMap[$v['produto_id']] ?? null;
    if (!$p) continue;
    $c = $cliMap[$v['cliente_id']] ?? null;
    if (!$c || !$c['vip']) continue;
    $resultado[] = [
        'Código Venda'     => $v['id_venda'],
        'Cliente VIP'      => $c['nome_cliente'],
        'Cidade'           => $c['cidade'],
        'UF'               => $c['uf'],
        'Produto Comprado' => $p['nome_produto'],
        'Quantidade'       => $v['qtd'],
        'Valor Unitário'   => $p['preco']
    ];
}
usort($resultado, fn($a, $b) => $a['Código Venda'] <=> $b['Código Venda']);
PHP,
                'run' => function () use ($produtos, $clientes) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }
                    $cliMap = [];
                    foreach ($clientes as $c) {
                        $cliMap[$c['id_cliente']] = $c;
                    }

                    $vendasSql = DB::query('
                        SELECT id_venda, produto_id, cliente_id, qtd
                        FROM vendas_sql
                        WHERE status = :status
                    ', ['status' => 'concluido']);

                    $resultado = [];
                    foreach ($vendasSql as $v) {
                        $p = $prodMap[$v['produto_id']] ?? null;
                        if (!$p) {
                            continue;
                        }
                        $c = $cliMap[$v['cliente_id']] ?? null;
                        if (!$c || !$c['vip']) {
                            continue;
                        }
                        $resultado[] = [
                            'Código Venda'     => $v['id_venda'],
                            'Cliente VIP'      => $c['nome_cliente'],
                            'Cidade'           => $c['cidade'],
                            'UF'               => $c['uf'],
                            'Produto Comprado' => $p['nome_produto'],
                            'Quantidade'       => $v['qtd'],
                            'Valor Unitário'   => $p['preco'],
                        ];
                    }
                    usort($resultado, fn($a, $b) => $a['Código Venda'] <=> $b['Código Venda']);
                    return $resultado;
                },
            ];

        case 'agregacoes_escalares':
            return [
                'codigo' => <<<'PHP'
$prodMap = [];
foreach ($produtos as $p) { $prodMap[$p['id_produto']] = $p; }

$totalVendas = 0;
$totalItens = 0;
$faturamento = 0.0;
foreach ($vendas as $v) {
    if ($v['status'] === 'concluido') {
        $totalVendas++;
        $totalItens += $v['qtd'];
        $p = $prodMap[$v['produto_id']] ?? null;
        if ($p) {
            $faturamento += $v['qtd'] * $p['preco'];
        }
    }
}
$ticketMedio = $totalVendas > 0 ? round($faturamento / $totalVendas, 2) : 0;

$menorPreco = null;
$maiorEstoque = null;
foreach ($produtos as $p) {
    if ($menorPreco === null || $p['preco'] < $menorPreco) $menorPreco = $p['preco'];
    if ($maiorEstoque === null || $p['estoque'] > $maiorEstoque) $maiorEstoque = $p['estoque'];
}

$resultado = [
    ['Métrica' => 'Total de Vendas Concluídas', 'Valor' => $totalVendas, 'Método' => 'count nativo'],
    ['Métrica' => 'Quantidade Total de Itens Vendidos', 'Valor' => $totalItens, 'Método' => 'sum nativo'],
    ['Métrica' => 'Faturamento Bruto', 'Valor' => 'R$ ' . number_format($faturamento, 2, ',', '.'), 'Método' => 'loop nativo'],
    ['Métrica' => 'Ticket Médio por Venda', 'Valor' => 'R$ ' . number_format($ticketMedio, 2, ',', '.'), 'Método' => 'avg nativo'],
    ['Métrica' => 'Menor Preço de Produto', 'Valor' => 'R$ ' . number_format((float)$menorPreco, 2, ',', '.'), 'Método' => 'min nativo'],
    ['Métrica' => 'Maior Estoque Individual', 'Valor' => $maiorEstoque . ' un', 'Método' => 'max nativo'],
];
PHP,
                'run' => function () use ($vendas, $produtos) {
                    $prodMap = [];
                    foreach ($produtos as $p) {
                        $prodMap[$p['id_produto']] = $p;
                    }

                    $totalVendas = 0;
                    $totalItens = 0;
                    $faturamento = 0.0;
                    foreach ($vendas as $v) {
                        if ($v['status'] === 'concluido') {
                            $totalVendas++;
                            $totalItens += $v['qtd'];
                            $p = $prodMap[$v['produto_id']] ?? null;
                            if ($p) {
                                $faturamento += $v['qtd'] * $p['preco'];
                            }
                        }
                    }
                    $ticketMedio = $totalVendas > 0 ? round($faturamento / $totalVendas, 2) : 0;

                    $menorPreco = null;
                    $maiorEstoque = null;
                    foreach ($produtos as $p) {
                        if ($menorPreco === null || $p['preco'] < $menorPreco) {
                            $menorPreco = $p['preco'];
                        }
                        if ($maiorEstoque === null || $p['estoque'] > $maiorEstoque) {
                            $maiorEstoque = $p['estoque'];
                        }
                    }

                    return [
                        ['Métrica' => 'Total de Vendas Concluídas', 'Valor' => $totalVendas, 'Método' => 'count nativo'],
                        ['Métrica' => 'Quantidade Total de Itens Vendidos', 'Valor' => $totalItens, 'Método' => 'sum nativo'],
                        ['Métrica' => 'Faturamento Bruto', 'Valor' => 'R$ ' . number_format($faturamento, 2, ',', '.'), 'Método' => 'loop nativo'],
                        ['Métrica' => 'Ticket Médio por Venda', 'Valor' => 'R$ ' . number_format($ticketMedio, 2, ',', '.'), 'Método' => 'avg nativo'],
                        ['Métrica' => 'Menor Preço de Produto', 'Valor' => 'R$ ' . number_format((float)$menorPreco, 2, ',', '.'), 'Método' => 'min nativo'],
                        ['Métrica' => 'Maior Estoque Individual', 'Valor' => $maiorEstoque . ' un', 'Método' => 'max nativo'],
                    ];
                },
            ];

        case 'paginacao':
            return [
                'codigo' => <<<'PHP'
$ordenadas = $vendas;
usort($ordenadas, static fn(array $a, array $b): int => $a['id_venda'] <=> $b['id_venda']);
$resultado = array_slice($ordenadas, 5, 5);
PHP,
                'run' => static function () use ($vendas): array {
                    $ordenadas = $vendas;
                    usort($ordenadas, static fn(array $a, array $b): int => $a['id_venda'] <=> $b['id_venda']);

                    return array_slice($ordenadas, 5, 5);
                },
            ];

        default:
            return [
                'codigo' => <<<'PHP'
$resultado = [];
PHP,
                'run' => fn() => [],
            ];
    }
};

// Medidor de Benchmark em Alta Resolução (nanosegundos)
$executarBenchmark = function (callable $linqFn, callable $nativeFn, int $iterations = 500): array {
    $iterations = max(50, min(3000, $iterations));

    // Warm-up do JIT e caches
    for ($i = 0; $i < 20; $i++) {
        $linqFn();
        $nativeFn();
    }

    // Medição LinqPHP
    $startMemLinq = memory_get_usage();
    $t0 = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $linqFn();
    }
    $t1 = hrtime(true);
    $linqMemPeak = memory_get_peak_usage();
    $linqNs = $t1 - $t0;

    // Medição PHP Nativo
    $startMemNat = memory_get_usage();
    $t2 = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $nativeFn();
    }
    $t3 = hrtime(true);
    $natMemPeak = memory_get_peak_usage();
    $natNs = $t3 - $t2;

    $metrics = BenchmarkMetrics::calculate($linqNs, $natNs, $iterations);

    return [
        'iterations'  => $iterations,
        'linq'        => [
            'totalMs' => $metrics['linqTotalMs'],
            'usPerOp' => $metrics['linqUsPerOp'],
            'memory'  => sprintf('%.2f KB', ($linqMemPeak - $startMemLinq) / 1024),
        ],
        'native'      => [
            'totalMs' => $metrics['nativeTotalMs'],
            'usPerOp' => $metrics['nativeUsPerOp'],
            'memory'  => sprintf('%.2f KB', ($natMemPeak - $startMemNat) / 1024),
        ],
        'deltaUsPerOp' => $metrics['deltaUsPerOp'],
        'deltaTotalMs' => $metrics['deltaTotalMs'],
        'diffUs'       => $metrics['deltaUsPerOp'],
        'diffTotalMs'  => $metrics['deltaTotalMs'],
        'ratio'       => $metrics['ratio'],
    ];
};

$cenarioRunner = null;
try {
    $cenarioRunner = $compilarLinq($codigoAtivo);
    $linq = $avaliarLinq($cenarioRunner);
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

// Preparação dos dados nativos e benchmark inicial
$natData = $obterNativoCenario($cenarioAtual, $vendas, $produtos, $produtosOutlet, $clientes);
$linqFn = $cenarioRunner instanceof Closure
    ? fn() => $avaliarLinq($cenarioRunner)
    : static fn() => throw new RuntimeException('O pipeline do cenário não foi compilado.');

// Ação de benchmark real time sob demanda (?api=1&action=bench)
if (($_GET['action'] ?? '') === 'bench' || ($jsonInput['action'] ?? '') === 'bench') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $iteracoes = isset($_GET['iterations']) ? (int)$_GET['iterations'] : (isset($jsonInput['iterations']) ? (int)$jsonInput['iterations'] : 500);
    try {
        $bench = $executarBenchmark($linqFn, $natData['run'], $iteracoes);

        echo json_encode([
            'success'   => true,
            'cenario'   => $cenarioAtual,
            'benchmark' => $bench,
            'nativo'    => ['codigo' => $natData['codigo']],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Throwable $e) {
        echo json_encode([
            'success'   => false,
            'cenario'   => $cenarioAtual,
            'erro'      => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

// Exportação Direta via CSV (?export=csv ou ?format=csv)
if (($_GET['export'] ?? '') === 'csv' || ($_GET['format'] ?? '') === 'csv') {
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="linq-' . $cenarioAtual . '.csv"');
    }
    $fp = fopen('php://output', 'w');
    if ($fp !== false) {
        fwrite($fp, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        if ($resultado !== [] && is_array($resultado[0])) {
            fputcsv($fp, array_keys($resultado[0]), ';', '"', '\\');
            foreach ($resultado as $linha) {
                $row = array_map(function ($val) {
                    if ($val === null) {
                        return '';
                    }
                    if (is_bool($val)) {
                        return $val ? 'Sim' : 'Não';
                    }
                    if (is_array($val)) {
                        return json_encode($val, JSON_UNESCAPED_UNICODE);
                    }
                    return (string)$val;
                }, $linha);
                fputcsv($fp, $row, ';', '"', '\\');
            }
        }
        fclose($fp);
    }
    exit;
}

// Resposta para Endpoint JSON (API padrão)
$isApiRequest = isset($_GET['api'])
    || ($_GET['format'] ?? '') === 'json'
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json') && !isset($_GET['html']));

$benchInitial = null;
if ($erroExecucao === null) {
    try {
        $benchInitial = $executarBenchmark($linqFn, $natData['run'], 200);
    } catch (Throwable) {
        $benchInitial = null;
    }
}

if ($isApiRequest) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success'      => $erroExecucao === null,
        'cenario'      => $cenarioAtual,
        'meta'         => [
            'titulo' => $dadosCenario['titulo'],
            'tag'    => $dadosCenario['tag'],
            'cor'    => $dadosCenario['cor'],
            'desc'   => $dadosCenario['desc'],
        ],
        'codigo'       => $codigoAtivo,
        'codigoPadrao' => $dadosCenario['codigo'],
        'isCustom'     => $isCustomCode,
        'colunas'      => $resultado !== [] && is_array($resultado[0]) ? array_keys($resultado[0]) : [],
        'resultado'    => $resultado,
        'telemetria'   => [
            'elapsedTime' => $linq->elapsedTime ?? '0.000000',
            'memoryUsed'  => $linq->memoryUsed ?? '0.00 MB',
            'count'       => $linq->count ?? count($resultado),
        ],
        'benchmark'    => $benchInitial,
        'nativo'       => ['codigo' => $natData['codigo']],
        'erro'         => $erroExecucao,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Metadados serializáveis dos cenários para bootstrapping do Vue 3
$cenariosMeta = [];
foreach ($cenarios as $key => $c) {
    $cenariosMeta[$key] = [
        'titulo' => $c['titulo'],
        'tag'    => $c['tag'],
        'cor'    => $c['cor'],
        'desc'   => $c['desc'],
        'codigo' => $c['codigo'],
    ];
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
        $html .= '</div>';
        $html .= "<span class='badge rounded-pill fw-semibold' style='font-size: 0.72rem; {$badgeStyle}'>{$badgeCount} itens</span>";
        $html .= '</div>';
        $html .= "<div class='card-body p-0'><div class='table-responsive'><table class='table table-sm table-dark table-hover table-zebra mb-0 align-middle' style='font-size: 0.82rem; background: #161b22; border-color: #30363d;'>";
        $html .= "<thead style='background: #1c2128; border-bottom: 1px solid #30363d;'><tr>";

        foreach ($colunas as $col) {
            $html .= "<th class='text-secondary py-2 px-2' style='font-weight: 600; font-size: 0.74rem; letter-spacing: 0.04em; background: #1c2128; border-color: #30363d;'>" . strtoupper($col) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

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

                $html .= "<td class='py-2 px-2' style='color: #c9d1d9; border-color: #21262d;'>{$celula}</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div></div></div>';
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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%238B5CF6'/%3E%3Cstop offset='50%25' stop-color='%236366F1'/%3E%3Cstop offset='100%25' stop-color='%23FF7A00'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='%23161b22'/%3E%3Crect width='30' height='30' x='1' y='1' rx='7' fill='none' stroke='url(%23g)' stroke-width='1.5'/%3E%3Cpath d='M18 4L9 17h6l-2 11 11-14h-6l3-10z' fill='url(%23g)'/%3E%3C/svg%3E">
    <link rel="alternate icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>
    <style>
        [v-cloak] {
            opacity: 0;
        }

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

        .scenario-pill:focus-visible {
            outline: 3px solid #fbbf24;
            outline-offset: 3px;
        }

        .scenario-pill .tag-pill {
            background: rgba(255, 255, 255, 0.15);
        }

        .scenario-pill--dbphp {
            max-width: 100%;
            border-radius: 0.75rem;
            color: #f5eefe;
            background: linear-gradient(110deg, rgba(139, 92, 246, 0.18), rgba(255, 122, 0, 0.14));
            border-color: rgba(196, 160, 255, 0.7);
            white-space: normal;
        }

        .scenario-pill--dbphp:hover {
            color: #ffffff;
            background: linear-gradient(110deg, rgba(139, 92, 246, 0.3), rgba(255, 122, 0, 0.22));
            border-color: #fbbf24;
        }

        .scenario-pill--dbphp.active {
            color: #ffffff;
            background: linear-gradient(110deg, #6d3bc1, #a94c12);
            border-color: #fbbf24;
            box-shadow: 0 0 16px rgba(251, 191, 36, 0.24);
        }

        .scenario-pill--dbphp .tag-pill {
            color: #ffe08a;
            background: rgba(0, 0, 0, 0.24);
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

        .custom-result-tabs .nav-link {
            background: #161b22;
            border: 1px solid var(--brand-border);
            color: var(--brand-text-secondary);
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }

        .custom-result-tabs .nav-link:hover {
            color: #ffffff;
            border-color: var(--brand-purple);
        }

        .custom-result-tabs .nav-link.active {
            background: var(--brand-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 0 12px var(--brand-purple-glow);
        }

        .form-control:focus {
            background-color: #161b22 !important;
            border-color: var(--brand-purple) !important;
            box-shadow: 0 0 0 0.2rem var(--brand-purple-glow) !important;
            color: #f0f6fc !important;
        }

        .bench-card {
            background-color: var(--brand-card-bg);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .bench-card:hover {
            border-color: rgba(139, 92, 246, 0.5);
            transform: translateY(-2px);
        }

        .bench-pill {
            background: #21262d;
            border: 1px solid var(--brand-border);
            color: #8b949e;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .bench-pill:hover {
            color: #ffffff;
            border-color: var(--brand-purple);
        }

        .bench-pill.active {
            background: var(--brand-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 0 10px var(--brand-purple-glow);
        }

        /* Grid de Resultados Zebrada e Dark Theme */
        .table-zebra {
            --zebra-odd: #161b22;
            --zebra-even: #1c2129;
            --zebra-hover: #262c36;
            border-color: #30363d;
            font-size: 0.82rem;
        }

        .table-zebra thead th {
            background-color: #1c2128 !important;
            border-bottom: 1px solid #30363d !important;
            color: var(--brand-text-secondary);
            font-weight: 600;
            font-size: 0.74rem;
            letter-spacing: 0.04em;
        }

        .table-zebra tbody tr {
            border-color: #21262d;
            transition: background-color 0.15s ease;
        }

        .table-zebra tbody tr:nth-child(odd) td {
            background-color: var(--zebra-odd) !important;
        }

        .table-zebra tbody tr:nth-child(even) td {
            background-color: var(--zebra-even) !important;
        }

        .table-zebra tbody tr:hover td {
            background-color: var(--zebra-hover) !important;
            color: #ffffff !important;
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

    <div id="app" class="container-fluid px-4" v-cloak>

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
                            <a
                                class="scenario-pill <?= $key === 'do_banco_ao_relatorio' ? 'scenario-pill--dbphp' : '' ?>"
                                :class="{ 'active': cenarioAtual === '<?= $key ?>' }"
                                :aria-current="cenarioAtual === '<?= $key ?>' ? 'page' : null"
                                href="?cenario=<?= $key ?>"
                                @click.prevent="selecionarCenario('<?= $key ?>')"
                            >
                                <?php if ($key === 'do_banco_ao_relatorio'): ?>
                                    <i class="bi bi-database" aria-hidden="true"></i>
                                <?php endif; ?>
                                <span class="badge rounded-pill tag-pill"><?= $cenario['tag'] ?></span>
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
                                    {{ cenarioAtualObj.tag || '<?= $dadosCenario['tag'] ?>' }}
                                </span>
                                <span v-if="isCustomCode" class="badge rounded-pill tag-pill" style="background: rgba(255, 122, 0, 0.25); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.45);">
                                    ⚡ Código Editado
                                </span>
                            </div>
                            <h4 class="fw-bold text-white mb-1" style="letter-spacing: -0.02em;">{{ cenarioAtualObj.titulo || '<?= $dadosCenario['titulo'] ?>' }}</h4>
                            <p class="mb-0" style="color: #8b949e; font-size: 0.92rem;">{{ cenarioAtualObj.desc || '<?= $dadosCenario['desc'] ?>' }}</p>
                        </div>
                    </div>

                    <div v-if="cenarioAtual === 'do_banco_ao_relatorio'" class="alert border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 my-3 rounded-3" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(255, 122, 0, 0.15) 100%); border: 1px solid rgba(139, 92, 246, 0.35) !important;">
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

                    <!-- Mensagem de Erro (se houver) -->
                    <div v-if="erroExecucao" class="alert shadow-sm border-0 d-flex align-items-start gap-3 my-3 rounded-3 p-3" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4) !important; color: #fca5a5;">
                        <span class="fs-4">⚠️</span>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Falha na Execução do Código:</h6>
                            <div class="font-monospace small text-break">{{ erroExecucao }}</div>
                        </div>
                    </div>

                    <!-- Card de Telemetria / Performance -->
                    <div class="hud-telemetry p-3 my-3 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; color: #8b949e; font-size: 0.72rem;">TELEMETRIA LINQ:</span>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.45);">
                                ⏱️ {{ telemetria.elapsedTime }}s
                            </span>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(0, 163, 255, 0.18); color: #7dd3fc; border: 1px solid rgba(0, 163, 255, 0.35);">
                                💾 {{ telemetria.memoryUsed }}
                            </span>
                            <span v-if="carregando" class="spinner-border spinner-border-sm text-purple-light ms-1" role="status"></span>
                        </div>
                        <div>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: rgba(255, 122, 0, 0.2); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.4); font-size: 0.85rem;">
                                📝 {{ telemetria.count }} {{ telemetria.count === 1 ? 'linha gerada' : 'linhas geradas' }}
                            </span>
                        </div>
                    </div>

                    <!-- Bloco de Código Editável (Sandbox) -->
                    <form method="POST" :action="'?cenario=' + encodeURIComponent(cenarioAtual)" id="formEditor" class="mt-2 mb-4" @submit.prevent="executarCodigo">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: #0b0f19; border: 1px solid #30363d !important;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3" style="background-color: #161b22; border-bottom: 1px solid #30363d;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.05em;">💻 Pipeline LINQ Editável</span>
                                    <span class="small d-none d-sm-inline" style="color: #8b949e;">(PHP 8.1+)</span>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 font-monospace" style="font-size: 0.7rem;">
                                        <i class="bi bi-shield-check me-1"></i> Sandbox Blindada
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button
                                        v-if="isCustomCode"
                                        type="button"
                                        class="btn btn-sm btn-nav-action py-1 px-2"
                                        style="font-size: 0.78rem;"
                                        @click="restaurarPadrao"
                                    >
                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrão
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-gradient-run py-1 px-3 d-flex align-items-center gap-1"
                                        style="font-size: 0.82rem;"
                                        :disabled="carregando"
                                    >
                                        <span v-if="carregando" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        <i v-else class="bi bi-play-fill fs-6"></i>
                                        <span>{{ carregando ? 'Executando...' : 'Simular / Executar' }}</span>
                                        <span class="d-none d-md-inline opacity-75 small">(Ctrl+Enter)</span>
                                    </button>
                                </div>
                            </div>
                            <div class="p-2">
                                <textarea
                                    name="codigo_customizado"
                                    id="codigoEditor"
                                    v-model="codigoAtivo"
                                    @keydown="handleKeydown"
                                    class="form-control font-monospace border-0 bg-transparent p-2"
                                    rows="8"
                                    spellcheck="false"
                                    style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 0.88rem; line-height: 1.5; resize: vertical; outline: none; box-shadow: none; color: #38bdf8 !important;"
                                ></textarea>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center py-2 px-3" style="background-color: #0b0f19; border-top: 1px solid #21262d; font-size: 0.75rem;">
                                <span style="color: #8b949e;">
                                    <strong class="text-light">Variáveis disponíveis:</strong> <code class="text-info">$vendas</code>, <code class="text-info">$produtos</code>, <code class="text-info">$produtosOutlet</code>, <code class="text-info">$clientes</code> | <code class="text-warning">JoinType::*</code>, <code class="text-warning">DB::query()</code> (tabela <code class="text-info">vendas_sql</code>)
                                </span>
                                <span class="d-none d-md-inline" style="color: #6e7681;">💡 Use Tab para indentar e Ctrl+Enter para executar</span>
                            </div>
                        </div>
                    </form>

                    <!-- Abas de Navegação do Resultado (Grid Tabela, Código, JSON Bruto) -->
                    <div class="mt-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <ul class="nav nav-pills custom-result-tabs gap-2 mb-0">
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-2"
                                        :class="{ 'active': abaAtiva === 'tabela' }"
                                        @click="abaAtiva = 'tabela'"
                                    >
                                        <i class="bi bi-table"></i>
                                        <span>Grid Tabela</span>
                                        <span class="badge rounded-pill" :class="abaAtiva === 'tabela' ? 'bg-white text-dark' : 'bg-dark text-secondary'" style="font-size: 0.7rem;">
                                            {{ resultado.length }}
                                        </span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-2"
                                        :class="{ 'active': abaAtiva === 'codigo' }"
                                        @click="abaAtiva = 'codigo'"
                                    >
                                        <i class="bi bi-code-slash"></i>
                                        <span>Código LINQ</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-2"
                                        :class="{ 'active': abaAtiva === 'json' }"
                                        @click="abaAtiva = 'json'"
                                    >
                                        <i class="bi bi-braces"></i>
                                        <span>JSON Bruto</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-2"
                                        :class="{ 'active': abaAtiva === 'bench' }"
                                        @click="abaAtiva = 'bench'"
                                    >
                                        <i class="bi bi-lightning-charge-fill text-warning"></i>
                                        <span>Benchmark vs PHP Nativo</span>
                                        <span v-if="benchData" class="badge rounded-pill" :class="abaAtiva === 'bench' ? 'bg-white text-dark' : 'bg-dark text-warning'" style="font-size: 0.7rem;">
                                            {{ benchData.linq?.usPerOp }}µs
                                        </span>
                                    </button>
                                </li>
                            </ul>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge rounded-pill" style="background: #21262d; border: 1px solid #30363d; color: #8b949e; font-size: 0.75rem;">
                                    {{ resultado.length }} {{ resultado.length === 1 ? 'linha retornada' : 'linhas retornadas' }}
                                </span>

                                <!-- Ferramentas de Exportação Rápida -->
                                <!--
                                <div class="btn-group btn-group-sm" role="group">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-nav-action px-2 py-1 d-flex align-items-center gap-1 rounded-start-pill"
                                        style="font-size: 0.75rem;"
                                        @click="baixarCsv"
                                        :disabled="resultado.length === 0"
                                        title="Baixar planilha CSV formatada (UTF-8 com BOM)"
                                    >
                                        <i class="bi bi-filetype-csv text-success"></i>
                                        <span>CSV</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-nav-action px-2 py-1 d-flex align-items-center gap-1"
                                        style="font-size: 0.75rem;"
                                        @click="copiarArrayPhp"
                                        :disabled="resultado.length === 0"
                                        title="Copiar resultado como Array literal PHP"
                                    >
                                        <i :class="copiadoPhp ? 'bi bi-check2 text-success' : 'bi bi-filetype-php text-info'"></i>
                                        <span>{{ copiadoPhp ? 'Copiado!' : 'Array PHP' }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-nav-action px-2 py-1 d-flex align-items-center gap-1 rounded-end-pill"
                                        style="font-size: 0.75rem;"
                                        @click="copiarTexto(jsonFormatado, 'json')"
                                        :disabled="resultado.length === 0"
                                        title="Copiar JSON formatado para a área de transferência"
                                    >
                                        <i :class="copiadoJson ? 'bi bi-check2 text-success' : 'bi bi-braces text-warning'"></i>
                                        <span>{{ copiadoJson ? 'Copiado!' : 'JSON' }}</span>
                                    </button>
                                </div>
                                -->
                            </div>
                        </div>

                        <!-- Conteúdo da Aba 1: Grid Tabela -->
                        <div v-show="abaAtiva === 'tabela'">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div class="input-group input-group-sm" style="max-width: 320px;">
                                    <span class="input-group-text border-end-0" style="background: #161b22; border-color: #30363d; color: #8b949e;">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input
                                        type="text"
                                        v-model="filtroTabela"
                                        class="form-control border-start-0"
                                        placeholder="Filtrar dados na tabela..."
                                        style="background: #161b22; border-color: #30363d; color: #f0f6fc; font-size: 0.8rem;"
                                    >
                                    <button
                                        v-if="filtroTabela"
                                        class="btn btn-outline-secondary border-start-0"
                                        type="button"
                                        @click="filtroTabela = ''"
                                        style="border-color: #30363d; background: #161b22; color: #8b949e;"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div v-if="filtroTabela" class="small text-secondary">
                                    Filtrando: <span class="text-white fw-bold">{{ linhasFiltradas.length }}</span> de <span class="text-white fw-bold">{{ resultado.length }}</span>
                                </div>
                            </div>

                            <div v-if="linhasFiltradas.length === 0" class="alert alert-secondary py-3 mb-3 text-secondary text-center rounded-3" style="background: #161b22; border: 1px solid #30363d; font-size: 0.85rem;">
                                <i class="bi bi-inbox me-2"></i>Nenhum registro encontrado.
                            </div>

                            <div v-else class="card mb-3 rounded-3 overflow-hidden shadow-sm" style="background: #161b22; border: 1px solid #30363d;">
                                <div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background: #21262d; border-bottom: 1px solid #30363d;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #FF7A00; display: inline-block; box-shadow: 0 0 8px #FF7A00;"></span>
                                        <span class="fw-semibold text-white" style="font-size: 0.85rem;">Dados Processados</span>
                                    </div>
                                    <span class="badge rounded-pill fw-semibold" style="font-size: 0.72rem; background: rgba(255, 122, 0, 0.18); color: #fdba74; border: 1px solid rgba(255, 122, 0, 0.4);">
                                        {{ linhasFiltradas.length }} itens
                                    </span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-dark table-hover table-zebra mb-0 align-middle" style="font-size: 0.82rem; border-color: #30363d;">
                                            <thead style="background: #1c2128; border-bottom: 1px solid #30363d;">
                                                <tr>
                                                    <th v-for="col in colunas" :key="col" class="text-secondary py-2 px-2" style="font-weight: 600; font-size: 0.74rem; letter-spacing: 0.04em; background: #1c2128; border-color: #30363d;">
                                                        {{ String(col).toUpperCase() }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(linha, index) in linhasFiltradas" :key="index" style="border-color: #21262d;">
                                                    <td v-for="col in colunas" :key="col" class="py-2 px-2" style="color: #c9d1d9; border-color: #21262d;">
                                                        <span v-if="isNull(linha[col])" class="badge font-monospace" style="font-size: 0.68rem; background: rgba(110, 118, 129, 0.2); color: #8b949e; border: 1px solid #30363d;">null</span>
                                                        <span v-else-if="isBool(linha[col])" class="badge" :style="linha[col] ? 'background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35);'">
                                                            {{ linha[col] ? 'Sim' : 'Não' }}
                                                        </span>
                                                        <span v-else-if="isStatus(col)" class="badge rounded-pill text-uppercase" style="font-size: 0.68rem;" :style="obterEstiloStatus(linha[col])">
                                                            {{ linha[col] }}
                                                        </span>
                                                        <span v-else-if="isVip(col)" class="badge" :style="linha[col] ? 'background: rgba(255, 122, 0, 0.2); color: #fb923c; border: 1px solid rgba(255, 122, 0, 0.4);' : 'background: rgba(110, 118, 129, 0.15); color: #8b949e; border: 1px solid #30363d;'">
                                                            {{ linha[col] ? 'VIP ⭐' : 'Comum' }}
                                                        </span>
                                                        <span v-else-if="isDecimal(linha[col])" class="fw-semibold text-light">
                                                            {{ formatarDecimal(linha[col]) }}
                                                        </span>
                                                        <code v-else-if="isObject(linha[col])" class="small text-info">
                                                            {{ formatarJson(linha[col]) }}
                                                        </code>
                                                        <span v-else>{{ linha[col] }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conteúdo da Aba 2: Código LINQ Executado -->
                        <div v-show="abaAtiva === 'codigo'">
                            <div class="card border-0 rounded-3 overflow-hidden shadow-sm" style="background: #0b0f19; border: 1px solid #30363d !important;">
                                <div class="d-flex justify-content-between align-items-center py-2 px-3" style="background: #161b22; border-bottom: 1px solid #30363d;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-code text-purple-light"></i>
                                        <span class="text-white fw-bold small">Pipeline PHP Executado</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-nav-action py-1 px-3 d-flex align-items-center gap-1 rounded-pill"
                                        style="font-size: 0.78rem;"
                                        @click="copiarTexto(codigoAtivo, 'codigo')"
                                    >
                                        <i :class="copiadoCodigo ? 'bi bi-check2 text-success' : 'bi bi-clipboard'"></i>
                                        <span>{{ copiadoCodigo ? 'Copiado!' : 'Copiar Código' }}</span>
                                    </button>
                                </div>
                                <div class="p-3">
                                    <pre class="m-0 font-monospace" style="color: #38bdf8; font-size: 0.85rem; line-height: 1.6; white-space: pre-wrap; word-break: break-word;"><code>{{ codigoAtivo }}</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Conteúdo da Aba 3: JSON Bruto -->
                        <div v-show="abaAtiva === 'json'">
                            <div class="card border-0 rounded-3 overflow-hidden shadow-sm" style="background: #0b0f19; border: 1px solid #30363d !important;">
                                <div class="d-flex justify-content-between align-items-center py-2 px-3" style="background: #161b22; border-bottom: 1px solid #30363d;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-braces text-warning"></i>
                                        <span class="text-white fw-bold small">Payload JSON Completo</span>
                                        <span class="badge rounded-pill bg-dark text-secondary" style="font-size: 0.7rem;">{{ resultado.length }} registros</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-nav-action py-1 px-3 d-flex align-items-center gap-1 rounded-pill"
                                            style="font-size: 0.78rem;"
                                            @click="baixarJson"
                                            title="Baixar arquivo JSON"
                                        >
                                            <i class="bi bi-download text-warning"></i>
                                            <span>Baixar .json</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-nav-action py-1 px-3 d-flex align-items-center gap-1 rounded-pill"
                                            style="font-size: 0.78rem;"
                                            @click="copiarTexto(jsonFormatado, 'json')"
                                        >
                                            <i :class="copiadoJson ? 'bi bi-check2 text-success' : 'bi bi-clipboard'"></i>
                                            <span>{{ copiadoJson ? 'Copiado!' : 'Copiar JSON' }}</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-3" style="max-height: 60vh; overflow-y: auto;">
                                    <pre class="m-0 font-monospace" style="color: #a5b4fc; font-size: 0.82rem; line-height: 1.5; white-space: pre-wrap;"><code>{{ jsonFormatado }}</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Conteúdo da Aba 4: Benchmark vs PHP Nativo -->
                        <div v-show="abaAtiva === 'bench'">
                            <!-- Painel Superior de Ação do Benchmark -->
                            <div class="card mb-3 rounded-3 p-3 shadow-sm" style="background: linear-gradient(135deg, #161b22 0%, #1a2234 100%); border: 1px solid rgba(139, 92, 246, 0.35);">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fs-5">⚡</span>
                                            <h6 class="fw-bold text-white mb-0" style="letter-spacing: -0.01em;">
                                                Benchmark em Alta Resolução (PHP hrtime)
                                            </h6>
                                            <span class="badge rounded-pill" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35); font-size: 0.7rem;">
                                                Nanosegundos Nativo
                                            </span>
                                        </div>
                                        <p class="small mb-0" style="color: #8b949e; font-size: 0.82rem;">
                                            Executa o mesmo pipeline em paralelo via <strong>LinqPHP</strong> e via <strong>PHP Puro procedural</strong> com warm-up prévio de CPU/JIT.
                                        </p>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="small text-secondary fw-semibold">Iterações:</span>
                                        <div class="d-flex gap-1">
                                            <button
                                                v-for="it in [100, 500, 1000, 2000]"
                                                :key="it"
                                                type="button"
                                                class="bench-pill"
                                                :class="{ 'active': iteracoesBench === it }"
                                                @click="executarBenchmark(it)"
                                                :disabled="carregandoBench"
                                            >
                                                {{ it }}
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-gradient-run py-1 px-3 d-flex align-items-center gap-2 rounded-pill ms-2"
                                            @click="executarBenchmark()"
                                            :disabled="carregandoBench"
                                        >
                                            <span v-if="carregandoBench" class="spinner-border spinner-border-sm" role="status"></span>
                                            <i v-else class="bi bi-play-circle-fill"></i>
                                            <span>{{ carregandoBench ? 'Medindo...' : 'Executar Teste' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Cards Comparativos de Métricas (LinqPHP vs Nativo vs Veredito) -->
                            <div v-if="benchData" class="row g-3 mb-3">
                                <!-- Card LinqPHP -->
                                <div class="col-md-4">
                                    <div class="card h-100 rounded-3 p-3 bench-card shadow-sm" style="background: #161b22; border: 1px solid rgba(139, 92, 246, 0.45);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-white small d-flex align-items-center gap-2">
                                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #8B5CF6; display: inline-block; box-shadow: 0 0 8px #8B5CF6;"></span>
                                                LinqPHP (Fluente)
                                            </span>
                                            <span class="badge rounded-pill" style="background: rgba(139, 92, 246, 0.2); color: #c4b5fd; font-size: 0.7rem;">
                                                OOP / Fluente
                                            </span>
                                        </div>
                                        <div class="my-2">
                                            <div class="display-6 fw-bold" style="color: #c4b5fd; font-size: 1.75rem;">
                                                {{ benchData.linq?.usPerOp }} <span class="fs-6 fw-normal text-secondary">µs / op</span>
                                            </div>
                                            <div class="small text-secondary mt-1">
                                                Tempo total: <strong class="text-white">{{ benchData.linq?.totalMs }} ms</strong> ({{ benchData.iterations }} ops)
                                            </div>
                                            <div class="small text-secondary">
                                                Pico de Memória: <strong class="text-white">{{ benchData.linq?.memory }}</strong>
                                            </div>
                                        </div>
                                        <div class="mt-auto pt-2 border-top border-secondary border-opacity-25 small" style="color: #a78bfa; font-size: 0.75rem;">
                                            <i class="bi bi-stars me-1"></i> Interface fluida, sem loops aninhados
                                        </div>
                                    </div>
                                </div>

                                <!-- Card PHP Nativo -->
                                <div class="col-md-4">
                                    <div class="card h-100 rounded-3 p-3 bench-card shadow-sm" style="background: #161b22; border: 1px solid rgba(34, 197, 94, 0.45);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-white small d-flex align-items-center gap-2">
                                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #22C55E; display: inline-block; box-shadow: 0 0 8px #22C55E;"></span>
                                                PHP Nativo (Loops)
                                            </span>
                                            <span class="badge rounded-pill" style="background: rgba(34, 197, 94, 0.2); color: #86efac; font-size: 0.7rem;">
                                                Procedural Puro
                                            </span>
                                        </div>
                                        <div class="my-2">
                                            <div class="display-6 fw-bold" style="color: #86efac; font-size: 1.75rem;">
                                                {{ benchData.native?.usPerOp }} <span class="fs-6 fw-normal text-secondary">µs / op</span>
                                            </div>
                                            <div class="small text-secondary mt-1">
                                                Tempo total: <strong class="text-white">{{ benchData.native?.totalMs }} ms</strong> ({{ benchData.iterations }} ops)
                                            </div>
                                            <div class="small text-secondary">
                                                Pico de Memória: <strong class="text-white">{{ benchData.native?.memory }}</strong>
                                            </div>
                                        </div>
                                        <div class="mt-auto pt-2 border-top border-secondary border-opacity-25 small" style="color: #4ade80; font-size: 0.75rem;">
                                            <i class="bi bi-cpu me-1"></i> Loops manuais e acumulação
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Veredito / Diferença -->
                                <div class="col-md-4">
                                    <div class="card h-100 rounded-3 p-3 bench-card shadow-sm" style="background: #161b22; border: 1px solid rgba(255, 122, 0, 0.45);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-white small d-flex align-items-center gap-2">
                                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #FF7A00; display: inline-block; box-shadow: 0 0 8px #FF7A00;"></span>
                                                Overhead & Veredito
                                            </span>
                                            <span class="badge rounded-pill" style="background: rgba(255, 122, 0, 0.2); color: #fdba74; font-size: 0.7rem;">
                                                Diferença Prática
                                            </span>
                                        </div>
                                        <div class="my-2">
                                            <div class="display-6 fw-bold" style="color: #fdba74; font-size: 1.75rem;">
                                                {{ benchData.deltaUsPerOp > 0 ? '+' : '' }}{{ benchData.deltaUsPerOp }}
                                                <span class="fs-6 fw-normal text-secondary">µs / op</span>
                                            </div>
                                            <div class="small text-secondary mt-1">
                                                <strong :class="benchmarkVerdict.class">{{ benchmarkVerdict.label }}</strong>
                                            </div>
                                            <div class="small text-secondary">
                                                Razão LinqPHP / nativo:
                                                <strong class="text-white">{{ benchData.ratio === null ? 'indisponível' : benchData.ratio + 'x' }}</strong>
                                            </div>
                                            <div class="small text-secondary">
                                                Delta acumulado no benchmark:
                                                <strong class="text-white">{{ benchData.deltaTotalMs > 0 ? '+' : '' }}{{ benchData.deltaTotalMs }} ms</strong>
                                                em {{ benchData.iterations }} execuções.
                                            </div>
                                            <div class="small text-secondary mt-1">
                                                Mede apenas o tempo de execução PHP; não representa a latência total HTTP, de rede ou do banco.
                                            </div>
                                        </div>
                                        <div class="mt-auto pt-2 border-top border-secondary border-opacity-25 small" style="color: #fed7aa; font-size: 0.75rem;">
                                            <i class="bi bi-info-circle me-1"></i> O sinal do delta é LinqPHP menos PHP nativo.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Barra Comparativa Visual Horizontal -->
                            <div v-if="benchData" class="card mb-3 rounded-3 p-3 shadow-sm" style="background: #161b22; border: 1px solid #30363d;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-white text-uppercase" style="letter-spacing: 0.04em; font-size: 0.78rem;">
                                        📊 Escala Comparativa por Operação (Microsegundos)
                                    </span>
                                    <span class="small text-secondary">
                                        {{ benchData.iterations }} iterações medidas com alta resolução
                                    </span>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-semibold text-success">PHP Nativo ({{ benchData.native?.usPerOp }} µs)</span>
                                        <span class="text-secondary">{{ calcularPercentual(benchData.native?.usPerOp, benchData.linq?.usPerOp) }}%</span>
                                    </div>
                                    <div class="progress" style="height: 10px; background-color: #21262d;">
                                        <div
                                            class="progress-bar"
                                            role="progressbar"
                                            :style="{ width: calcularPercentual(benchData.native?.usPerOp, benchData.linq?.usPerOp) + '%', background: '#22c55e' }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-semibold" style="color: #c4b5fd;">LinqPHP ({{ benchData.linq?.usPerOp }} µs)</span>
                                        <span class="text-secondary">100% (Baseline + Overhead)</span>
                                    </div>
                                    <div class="progress" style="height: 10px; background-color: #21262d;">
                                        <div
                                            class="progress-bar"
                                            role="progressbar"
                                            style="width: 100%; background: var(--brand-gradient);"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Blocos de Código Lado a Lado (Comparativo DX: LinqPHP vs PHP Puro) -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-0 rounded-3 overflow-hidden shadow-sm h-100" style="background: #0b0f19; border: 1px solid #30363d !important;">
                                        <div class="d-flex justify-content-between align-items-center py-2 px-3" style="background: #161b22; border-bottom: 1px solid #30363d;">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark-code text-purple-light"></i>
                                                <span class="text-white fw-bold small">Pipeline LinqPHP</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-nav-action py-1 px-3 d-flex align-items-center gap-1 rounded-pill"
                                                style="font-size: 0.75rem;"
                                                @click="copiarTexto(codigoAtivo, 'codigo')"
                                            >
                                                <i :class="copiadoCodigo ? 'bi bi-check2 text-success' : 'bi bi-clipboard'"></i>
                                                <span>{{ copiadoCodigo ? 'Copiado!' : 'Copiar' }}</span>
                                            </button>
                                        </div>
                                        <div class="p-3" style="max-height: 480px; overflow-y: auto;">
                                            <pre class="m-0 font-monospace" style="color: #38bdf8; font-size: 0.82rem; line-height: 1.5; white-space: pre-wrap;"><code>{{ codigoAtivo }}</code></pre>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 rounded-3 overflow-hidden shadow-sm h-100" style="background: #0b0f19; border: 1px solid #30363d !important;">
                                        <div class="d-flex justify-content-between align-items-center py-2 px-3" style="background: #161b22; border-bottom: 1px solid #30363d;">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-cpu text-success"></i>
                                                <span class="text-white fw-bold small">Equivalente em PHP Puro (Procedural)</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-nav-action py-1 px-3 d-flex align-items-center gap-1 rounded-pill"
                                                style="font-size: 0.75rem;"
                                                @click="copiarTexto(codigoNativo, 'nativo')"
                                            >
                                                <i :class="copiadoNativo ? 'bi bi-check2 text-success' : 'bi bi-clipboard'"></i>
                                                <span>{{ copiadoNativo ? 'Copiado!' : 'Copiar' }}</span>
                                            </button>
                                        </div>
                                        <div class="p-3" style="max-height: 480px; overflow-y: auto;">
                                            <pre class="m-0 font-monospace" style="color: #86efac; font-size: 0.82rem; line-height: 1.5; white-space: pre-wrap;"><code>{{ codigoNativo }}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        window.__LINQ_DATA__ = {
            cenarios: <?= json_encode($cenariosMeta, JSON_UNESCAPED_UNICODE) ?>,
            cenarioAtual: <?= json_encode($cenarioAtual, JSON_UNESCAPED_UNICODE) ?>,
            initialResult: <?= json_encode([
                'codigo'       => $codigoAtivo,
                'codigoPadrao' => $dadosCenario['codigo'],
                'isCustom'     => $isCustomCode,
                'colunas'      => $resultado !== [] && is_array($resultado[0]) ? array_keys($resultado[0]) : [],
                'resultado'    => $resultado,
                'telemetria'   => [
                    'elapsedTime' => $linq->elapsedTime ?? '0.000000',
                    'memoryUsed'  => $linq->memoryUsed ?? '0.00 MB',
                    'count'       => $linq->count ?? count($resultado),
                ],
                'benchmark'    => $benchInitial,
                'nativo'       => ['codigo' => $natData['codigo']],
                'erro'         => $erroExecucao,
            ], JSON_UNESCAPED_UNICODE) ?>
        };

        const { createApp, ref, computed, onMounted } = Vue;
        const initial = window.__LINQ_DATA__ || {};

        createApp({
            setup() {
                const cenarios = ref(initial.cenarios || {});
                const cenarioAtual = ref(initial.cenarioAtual || 'join_triplo');
                const codigoAtivo = ref(initial.initialResult?.codigo || '');
                const codigoPadrao = ref(initial.initialResult?.codigoPadrao || '');
                const resultado = ref(initial.initialResult?.resultado || []);
                const colunas = ref(initial.initialResult?.colunas || []);
                const telemetria = ref(initial.initialResult?.telemetria || { elapsedTime: '0.000000', memoryUsed: '0.00 MB', count: 0 });
                const erroExecucao = ref(initial.initialResult?.erro || null);
                const carregando = ref(false);
                const abaAtiva = ref('tabela');
                const filtroTabela = ref('');
                const copiadoCodigo = ref(false);
                const copiadoJson = ref(false);
                const copiadoNativo = ref(false);

                // Estado reativo do Benchmark Live
                const benchData = ref(initial.initialResult?.benchmark || null);
                const codigoNativo = ref(initial.initialResult?.nativo?.codigo || '');
                const iteracoesBench = ref(500);
                const carregandoBench = ref(false);

                const cenarioAtualObj = computed(() => cenarios.value[cenarioAtual.value] || {});

                const isCustomCode = computed(() => {
                    const clean = str => (str || '').replace(/\r\n/g, '\n').trim();
                    return clean(codigoAtivo.value) !== clean(codigoPadrao.value);
                });

                const linhasFiltradas = computed(() => {
                    if (!filtroTabela.value.trim()) {
                        return resultado.value;
                    }
                    const termo = filtroTabela.value.toLowerCase().trim();
                    return resultado.value.filter(linha => {
                        return Object.values(linha).some(val => {
                            if (val === null || val === undefined) return false;
                            return String(val).toLowerCase().includes(termo);
                        });
                    });
                });

                const jsonFormatado = computed(() => {
                    const payload = {
                        cenario: cenarioAtual.value,
                        telemetria: telemetria.value,
                        count: resultado.value.length,
                        data: resultado.value
                    };
                    return JSON.stringify(payload, null, 2);
                });

                const selecionarCenario = async (key) => {
                    if (cenarioAtual.value === key && !isCustomCode.value) return;
                    cenarioAtual.value = key;
                    const meta = cenarios.value[key];
                    if (meta) {
                        codigoAtivo.value = meta.codigo;
                        codigoPadrao.value = meta.codigo;
                    }
                    window.history.pushState(null, '', `?cenario=${key}`);
                    await carregarCenario(key);
                };

                const carregarCenario = async (key) => {
                    carregando.value = true;
                    erroExecucao.value = null;
                    try {
                        const res = await fetch(`?api=1&cenario=${encodeURIComponent(key)}`);
                        const data = await res.json();
                        if (data.success) {
                            resultado.value = data.resultado || [];
                            colunas.value = data.colunas || [];
                            telemetria.value = data.telemetria || {};
                            codigoAtivo.value = data.codigo;
                            codigoPadrao.value = data.codigoPadrao;
                            if (data.benchmark) {
                                benchData.value = data.benchmark;
                            }
                            if (data.nativo?.codigo) {
                                codigoNativo.value = data.nativo.codigo;
                            }
                            erroExecucao.value = null;
                        } else {
                            erroExecucao.value = data.erro || 'Falha ao carregar cenário';
                            resultado.value = [];
                            colunas.value = [];
                        }
                    } catch (err) {
                        erroExecucao.value = 'Erro de rede: ' + err.message;
                    } finally {
                        carregando.value = false;
                    }
                };

                const executarCodigo = async () => {
                    carregando.value = true;
                    erroExecucao.value = null;
                    try {
                        const res = await fetch(`?api=1&cenario=${encodeURIComponent(cenarioAtual.value)}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                cenario: cenarioAtual.value,
                                codigo: codigoAtivo.value
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            resultado.value = data.resultado || [];
                            colunas.value = data.colunas || [];
                            telemetria.value = data.telemetria || {};
                            if (data.benchmark) {
                                benchData.value = data.benchmark;
                            }
                            if (data.nativo?.codigo) {
                                codigoNativo.value = data.nativo.codigo;
                            }
                            erroExecucao.value = null;
                        } else {
                            erroExecucao.value = data.erro || 'Erro na execução';
                            resultado.value = [];
                            colunas.value = [];
                            telemetria.value = data.telemetria || { elapsedTime: '0.000000', memoryUsed: '0.00 MB', count: 0 };
                        }
                    } catch (err) {
                        erroExecucao.value = 'Erro de rede ao executar código: ' + err.message;
                    } finally {
                        carregando.value = false;
                    }
                };

                const restaurarPadrao = async () => {
                    const meta = cenarios.value[cenarioAtual.value];
                    if (meta) {
                        codigoAtivo.value = meta.codigo;
                    }
                    await executarCodigo();
                };

                const calcularPercentual = (valA, valB) => {
                    const a = Number(valA) || 0;
                    const b = Number(valB) || 1;
                    if (b <= 0) return 100;
                    return Math.min(100, Math.max(5, Math.round((a / b) * 100)));
                };

                const benchmarkVerdict = computed(() => {
                    const delta = Number(benchData.value?.deltaUsPerOp);
                    if (!Number.isFinite(delta)) {
                        return { label: 'Veredito indisponível', class: 'text-secondary' };
                    }
                    if (delta > 0) {
                        return { label: 'LinqPHP mais lento nesta medição', class: 'text-warning' };
                    }
                    if (delta < 0) {
                        return { label: 'LinqPHP mais rápido nesta medição', class: 'text-success' };
                    }
                    return { label: 'Equivalente na precisão exibida', class: 'text-info' };
                });

                const executarBenchmark = async (it = null) => {
                    if (it) iteracoesBench.value = it;
                    carregandoBench.value = true;
                    try {
                        const res = await fetch(`?api=1&action=bench&cenario=${encodeURIComponent(cenarioAtual.value)}&iterations=${iteracoesBench.value}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'bench',
                                cenario: cenarioAtual.value,
                                codigo: codigoAtivo.value,
                                iterations: iteracoesBench.value
                            })
                        });
                        const data = await res.json();
                        if (data.success && data.benchmark) {
                            benchData.value = data.benchmark;
                            if (data.nativo?.codigo) {
                                codigoNativo.value = data.nativo.codigo;
                            }
                        }
                    } catch (err) {
                        console.error('Falha ao rodar benchmark:', err);
                    } finally {
                        carregandoBench.value = false;
                    }
                };

                const isNull = (val) => val === null || val === undefined;
                const isBool = (val) => typeof val === 'boolean';
                const isDecimal = (val) => typeof val === 'number' && !Number.isInteger(val);
                const isObject = (val) => typeof val === 'object' && val !== null;
                const isStatus = (col) => col === 'status';
                const isVip = (col) => col === 'vip';

                const obterEstiloStatus = (status) => {
                    switch (status) {
                        case 'concluido':
                            return 'background: rgba(34, 197, 94, 0.18); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4);';
                        case 'pendente':
                            return 'background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);';
                        case 'cancelado':
                            return 'background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);';
                        default:
                            return 'background: rgba(148, 163, 184, 0.18); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4);';
                    }
                };

                const formatarDecimal = (val) => Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const formatarJson = (val) => JSON.stringify(val);

                // Funções de Exportação Rápida (T-016)
                const copiadoPhp = ref(false);

                const gerarArrayPhpLiteral = (dados) => {
                    if (!dados || dados.length === 0) return '[]';
                    let php = "[\n";
                    for (const item of dados) {
                        php += "    [\n";
                        for (const [key, val] of Object.entries(item)) {
                            let strVal;
                            if (val === null) {
                                strVal = 'null';
                            } else if (typeof val === 'boolean') {
                                strVal = val ? 'true' : 'false';
                            } else if (typeof val === 'number') {
                                strVal = String(val);
                            } else if (typeof val === 'object') {
                                strVal = JSON.stringify(val);
                            } else {
                                strVal = "'" + String(val).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
                            }
                            php += `        '${key}' => ${strVal},\n`;
                        }
                        php += "    ],\n";
                    }
                    php += "]";
                    return php;
                };

                const copiarArrayPhp = async () => {
                    const texto = gerarArrayPhpLiteral(resultado.value);
                    try {
                        await navigator.clipboard.writeText(texto);
                        copiadoPhp.value = true;
                        setTimeout(() => copiadoPhp.value = false, 2000);
                    } catch (e) {
                        console.error('Falha ao copiar Array PHP:', e);
                    }
                };

                const baixarCsv = () => {
                    if (!resultado.value || resultado.value.length === 0) return;
                    const cols = colunas.value;
                    let csv = '\uFEFF'; // BOM UTF-8 para Excel
                    csv += cols.map(c => `"${String(c).replace(/"/g, '""')}"`).join(';') + '\r\n';

                    for (const linha of resultado.value) {
                        const linhaArr = cols.map(col => {
                            const val = linha[col];
                            if (val === null || val === undefined) return '""';
                            if (typeof val === 'boolean') return val ? '"Sim"' : '"Não"';
                            if (typeof val === 'object') return `"${JSON.stringify(val).replace(/"/g, '""')}"`;
                            return `"${String(val).replace(/"/g, '""')}"`;
                        });
                        csv += linhaArr.join(';') + '\r\n';
                    }

                    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', `linq-${cenarioAtual.value}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                };

                const baixarJson = () => {
                    const blob = new Blob([jsonFormatado.value], { type: 'application/json;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', `linq-${cenarioAtual.value}.json`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                };

                const copiarTexto = async (texto, tipo) => {
                    try {
                        await navigator.clipboard.writeText(texto);
                        if (tipo === 'codigo') {
                            copiadoCodigo.value = true;
                            setTimeout(() => copiadoCodigo.value = false, 2000);
                        } else if (tipo === 'nativo') {
                            copiadoNativo.value = true;
                            setTimeout(() => copiadoNativo.value = false, 2000);
                        } else {
                            copiadoJson.value = true;
                            setTimeout(() => copiadoJson.value = false, 2000);
                        }
                    } catch (e) {
                        console.error('Falha ao copiar:', e);
                    }
                };

                const handleKeydown = (e) => {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        const textarea = e.target;
                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        textarea.value = textarea.value.substring(0, start) + "    " + textarea.value.substring(end);
                        textarea.selectionStart = textarea.selectionEnd = start + 4;
                        codigoAtivo.value = textarea.value;
                    } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                        e.preventDefault();
                        executarCodigo();
                    }
                };

                onMounted(() => {
                    window.addEventListener('popstate', () => {
                        const params = new URLSearchParams(window.location.search);
                        const cenario = params.get('cenario') || 'join_triplo';
                        if (cenarios.value[cenario]) {
                            selecionarCenario(cenario);
                        }
                    });
                });

                return {
                    cenarios,
                    cenarioAtual,
                    cenarioAtualObj,
                    codigoAtivo,
                    codigoPadrao,
                    isCustomCode,
                    resultado,
                    colunas,
                    linhasFiltradas,
                    telemetria,
                    erroExecucao,
                    carregando,
                    abaAtiva,
                    filtroTabela,
                    jsonFormatado,
                    copiadoCodigo,
                    copiadoJson,
                    copiadoNativo,
                    copiadoPhp,
                    benchData,
                    benchmarkVerdict,
                    codigoNativo,
                    iteracoesBench,
                    carregandoBench,
                    selecionarCenario,
                    executarCodigo,
                    restaurarPadrao,
                    calcularPercentual,
                    executarBenchmark,
                    baixarCsv,
                    baixarJson,
                    copiarArrayPhp,
                    isNull,
                    isBool,
                    isDecimal,
                    isObject,
                    isStatus,
                    isVip,
                    obterEstiloStatus,
                    formatarDecimal,
                    formatarJson,
                    copiarTexto,
                    handleKeydown
                };
            }
        }).mount('#app');
    </script>
</body>

</html>
