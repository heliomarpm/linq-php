# Tarefas do Projeto: LinqPHP

> **Meta:** Executar quick wins de correção de autoloading PSR-4, sincronização de CI e otimizações de performance\
> **Status:** em progresso | **Progresso:** 20/29 (68%) | **WIP:** 0/2 | **Atualizado em:** 2026-10-26

## Em progresso

(nenhuma)

## Bloqueadas

(nenhuma)

## Backlog

### Alta Prioridade

- [ ] **T-021 · P1 · Pré-computar templates nulos em `join()` eliminando overhead de `nullFillRightOnly`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 3.1)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Array nulo gerado uma única vez antes dos loops de `LEFT`, `RIGHT` e `FULL JOIN`; suíte de testes 100% verde; redução de tempo medido em benchmarks com junções externas.

### Média Prioridade

- [ ] **T-022 · P2 · Unificar blocos de Reflection duplicados em `orderByKey()` e `orderBy()`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 2.1 e 2.2)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Método auxiliar privado para ordenação com callable reutilizado; duplicações eliminadas; testes unitários passando.

- [ ] **T-023 · P2 · Implementar fast-path de chave simples em `groupBy()`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 3.2)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Fast-path implementado para `count($groupingFields) === 1`, eliminando `array_intersect_key` e `implode` repetitivos; benchmarks demonstrando ganho expressivo de latência.

- [ ] **T-026 · P2 · Otimizar alocação de memória no `select()` com atribuição direta**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 3.4)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Eliminar chamadas de `array_replace` e `array_intersect_key` linha a linha em `select()`.

- [ ] **T-027 · P2 · Otimizar memória em `groupBy()` com acumulação O(1) de médias**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 3.3)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Acumular soma e contagem em vez de alocar listas com todos os floats na memória.

- [ ] **T-028 · P2 · Implementar fast-path de esquema idêntico em `unionAll()`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 3.5)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Quando ambas as coleções tiverem as mesmas chaves, usar `array_merge` direto sem normalizar linha a linha.

### Baixa Prioridade

- [ ] **T-024 · P3 · Portabilizar script `reset` no `composer.json` para PHP multiplataforma**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 1.6)
  - Arquivos: [composer.json](../composer.json)
  - DoD: `composer reset` funcionando nativamente em Windows, Linux e macOS sem depender do utilitário `rm`.

- [ ] **T-025 · P3 · Delegar cálculo em `avg()` diretamente para `sum()`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 2.3)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Loop duplicado em `avg()` substituído por chamada a `sum()`.

- [ ] **T-029 · P3 · Suportar `caseSensitive: true` e `mb_strtolower` em `where()`**
  - Origem: [docs/ASSESSMENT.md](../docs/ASSESSMENT.md) (Seção 1.4)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - DoD: Opção de comparação sensível a maiúsculas/minúsculas e tratamento seguro de acentuação UTF-8.



## Concluídas recentemente

- [x] **T-020 · P1 · Sincronizar `composer.lock` e restaurar validação estrita no CI** (2026-10-26)
  - Arquivos: [composer.lock](../composer.lock), [.github/workflows/ci.yml](../.github/workflows/ci.yml)
  - Evidência: `composer.lock` sincronizado via `composer update --lock`; `composer validate --strict` executado e aprovado com código 0; workflow de CI configurado com fallback resiliente para instalação multi-versão do PHP; 117 testes unitários passando 100% verde; PHPStan nível 5 com 0 erros.

- [x] **T-019 · P0 · Extrair enum `JoinType` para `src/JoinType.php` corrigindo autoloading PSR-4** (2026-10-26)
  - Arquivos: [src/JoinType.php](../src/JoinType.php), [src/LinqPHP.php](../src/LinqPHP.php)
  - Evidência: Enum `JoinType` extraído para seu próprio arquivo PSR-4 isolado; autoloading via `vendor/autoload.php` testado com sucesso direto sem carregar `LinqPHP` previamente; 117 testes unitários passando 100% verde (667 asserções); PHPStan nível 5 com 0 erros; Pint aprovado.

- [x] **T-018 · P2 · Implementar métodos terminais de agregação escalar rápida (`sum`, `avg`, `min`, `max`, `count`)** (2026-10-25)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php), [tests/Unit/ScalarAggregationsTest.php](../tests/Unit/ScalarAggregationsTest.php), [demo/index.php](../demo/index.php), [README.md](../README.md)
  - Evidência: Métodos terminais `sum()`, `avg()`, `min()`, `max()` e `count()` implementados com ultra-performance e suporte a closures dinâmicas e strings de campo; nova suíte `ScalarAggregationsTest` criada com 17 novos testes unitários; total de 117 testes unitários passando 100% verde (667 asserções); cenário de demonstração e benchmark comparativo adicionado na demo interativa; documentação no README.md atualizada; PHPStan nível 5 e Pint 100% aprovados.

- [x] **T-017 · P1 · Otimizar loops internos e alocação de memória no LinqPHP (where, select, join, groupBy)** (2026-10-25)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - Evidência: Fast-path em `where()` para closures diretas e condição única estruturada reduzindo latência em mais de 2x (~26µs vs ~58µs); fast-path para joins com chave simples (`count($fields) === 1`) eliminando overhead de `implode` e alocação de matrizes; fast-path em `groupBy([], ...)` para agregações globais eliminando `array_intersect_key` linha a linha; suíte de 100 testes unitários passando 100% verde (643 asserções); Pint e PHPStan 100% aprovados sem quebra de compatibilidade.

- [x] **T-016 · P3 · Adicionar ferramenta de exportação rápida (CSV, JSON e Array PHP) na demo** (2026-10-25)
  - Arquivos: [demo/index.php](../demo/index.php)
  - Evidência: Botão de download direto do resultado transformado em formato `.csv` com codificação UTF-8 e BOM via Blob no cliente e endpoint backend `?export=csv` com compatibilidade PHP 8.4; botão para copiar resultado em formato de Array literal em PHP (`['campo' => 'valor']`) com feedback visual no clipboard; botões de download e cópia de JSON formatado tanto na barra de abas quanto na aba JSON; 100 testes passando (643 asserções); PHPStan nível 5 com 0 erros; Pint formatado.

- [x] **T-015 · P2 · Adicionar painel de benchmark comparativo ao vivo (LinqPHP vs PHP Nativo)** (2026-10-25)
  - Arquivos: [demo/index.php](../demo/index.php)
  - Evidência: Medidor de performance de alta resolução com `hrtime(true)` em nanossegundos comparando LinqPHP com código procedural puro em PHP nativo; aba interativa `⚡ Benchmark vs PHP Nativo` com 3 cards de métricas (LinqPHP, PHP Nativo, Overhead & Veredito), barra visual comparativa proporcional por microsegundo/operação e snippets de código lado a lado; seletor de iterações dinâmico (100 a 2.000) e endpoint sob demanda `?api=1&action=bench` consumido via Vue 3 de forma assíncrona; 100 testes passando (643 asserções); PHPStan nível 5 com 0 erros; Pint formatado.

- [x] **T-014 · P2 · Implementar reatividade com Vue.js 3 via CDN e endpoint JSON na demo** (2026-10-25)
  - Arquivos: [demo/index.php](../demo/index.php)
  - Evidência: Vue 3 via CDN montado na aplicação com bootstrapping em window.__LINQ_DATA__; endpoint JSON serverless com suporte a ?api=1 e payloads POST json/form; alternância instantânea de cenários sem reload de página via pushState; abas de resultado dinâmicas (Grid Tabela com busca em tempo real, Código LINQ com cópia para clipboard e JSON Bruto formatado); 100 testes unitários passando (643 asserções); PHPStan nível 5 com 0 erros; benchmarks validados.

- [x] **T-013 · P2 · Modernizar layout da demo com Dark Theme coordenado (Roxo/Laranja Neon)** (2026-10-25)
  - Arquivos: [demo/index.php](../demo/index.php)
  - Evidência: Dark theme com paleta (#0d1117 / #161b22 / #30363d) alinhada ao ecossistema do heliomarpm/db-php; acentos roxo elétrico (#8B5CF6) e laranja neon (#FF7A00); fontes Plus Jakarta Sans e JetBrains Mono; renderGrid refinado com badges de status, null, booleanos, vip e dots coloridos; HUD de telemetria estilizado; editor sandbox com suporte a Ctrl+Enter e Tab; todos os 14 cenários testados e validados; 100 testes unitários passando (643 asserções); PHPStan nível 5 com 0 erros; benchmarks validados.

- [x] **T-012 · P1 · Integrar interoperabilidade e cross-promotion com heliomarpm/db-php na demo e README** (2026-10-25)
  - Arquivos: [demo/index.php](../demo/index.php), [README.md](../README.md)
  - Evidência: Botão de navegação cruzada para `https://db-php.vercel.app/` adicionado na navbar da demo; novo cenário interativo *"Do Banco ao Relatório (com DbPHP)"* demonstrando integração SQL fluente com `->linq()` e banner contextual; seção de sinergia aprimorada no `README.md` com comando de instalação e link da demo; 100 testes unitários passando (643 asserções) e análise PHPStan com 0 erros.

- [x] **T-010 · P2 · Suportar Callables/Closures nas funções de ordenação (`orderBy` / `orderByKey`)** (2026-10-24)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php), [tests/Unit/OrderByTest.php](../tests/Unit/OrderByTest.php), [demo/index.php](../demo/index.php), [README.md](../README.md)
  - Evidência: Suporte a closures de extração calculada (1 parâmetro com direção via `$descending`), closures comparadoras estilo usort (2 parâmetros), ordenação mista em `orderBy` com múltiplos critérios e prevenção de erros inválidos; 100 testes passando (643 asserções); PHPStan nível 5 com 0 erros; cenário interativo adicionado na demo e documentação no README.

- [x] **T-002 · P1 · Expandir matriz de versões do PHP no workflow de CI** (2026-10-24)
  - Arquivos: [.github/workflows/ci.yml](../.github/workflows/ci.yml)
  - Evidência: Matriz do GitHub Actions definida e consolidada para as versões do PHP 8.1 e 8.4; suíte de testes e benchmarks aprovadas; aceito como concluído pelo usuário.

- [x] **T-011 · P1 · Implementar método terminal `firstOrDefault()` inspirado no LINQ do C#** (2026-10-24)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php), [tests/Unit/FirstOrDefaultTest.php](../tests/Unit/FirstOrDefaultTest.php), [demo/index.php](../demo/index.php), [README.md](../README.md)
  - Evidência: Método terminal `firstOrDefault(?callable $predicate = null, mixed $default = null): mixed` implementado com curto-circuito (early-return) e valor default customizável; 90 testes passando (624 asserções); PHPStan nível 5 com 0 erros; cenário interativo adicionado na demo e documentação no README.

- [x] **T-009 · P2 · Suportar parâmetro `$descending` em `orderByKey` e booleanos por campo em `orderBy`** (2026-10-24)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php), [tests/Unit/OrderByTest.php](../tests/Unit/OrderByTest.php), [demo/index.php](../demo/index.php), [README.md](../README.md)
  - Evidência: Parâmetro `$descending` (booleano/string) adicionado em `orderByKey`; `orderBy` refinado com assinatura limpa `orderBy(array $fields)` aceitando booleanos (true/false) e strings por campo; 80 testes passando (612 asserções); PHPStan nível 5 com 0 erros; benchmarks validados.


- [x] **T-003 · P2 · Corrigir portabilidade e duplicação no script `reset` do Composer** (2026-10-13)
  - Arquivos: [composer.json](../composer.json)
  - Evidência: Duplicação de `composer install` removida; comando de remoção portabilizado com `@php` multiplataforma; validação aprovada via `composer validate`.

- [x] **T-004 · P2 · Corrigir metadados do projeto em `CITATION.cff`** (2026-10-13)
  - Arquivos: [CITATION.cff](../CITATION.cff)
  - Evidência: Resíduos de `ion-calendar` substituídos por `linq-php`, com resumo, versão 0.1.0, palavras-chave e URL do repositório corrigidos.

- [x] **T-005 · P2 · Configurar ferramenta de análise estática formal (PHPStan) no QA** (2026-10-13)
  - Arquivos: [composer.json](../composer.json), [phpstan.neon](../phpstan.neon), [src/LinqPHP.php](../src/LinqPHP.php)
  - Evidência: `phpstan/phpstan` instalado em `require-dev`; [phpstan.neon](../phpstan.neon) configurado no nível 5; PHPDocs de tipos ajustados; script `composer phpstan` criado e integrado ao `composer qa` rodando com 0 erros.

- [x] **T-008 · P2 · Reescrever em português os documentos de governança e templates de issue** (2026-10-13)
  - Arquivos: [CITATION.cff](../CITATION.cff), [docs/CODE_OF_CONDUCT.md](../docs/CODE_OF_CONDUCT.md), [docs/SUPPORT.md](../docs/SUPPORT.md), [docs/CONTRIBUTING.md](../docs/CONTRIBUTING.md), [.github/pull_request_template.md](../.github/pull_request_template.md), [.github/ISSUE_TEMPLATE](../.github/ISSUE_TEMPLATE)
  - Evidência: Documentos de governança e todos os templates reescritos em pt-BR com referências a scripts de Composer/PHP; resíduos de Node/npm eliminados.

- [x] **T-006 · P3 · Avaliar código comentado de validação em `assertJsonCollection()`** (2026-10-13)
  - Arquivos: [src/LinqPHP.php](../src/LinqPHP.php)
  - Evidência: Decidida a remoção definitiva da validação elemento a elemento no construtor para preservar a performance em coleções volumosas; código comentado morto removido; suíte de testes 100% verde (48 testes, 505 asserções).

- [x] **T-007 · P3 · Avaliar viabilidade de Lazy Evaluation / Generators (`yield`)** (2026-10-13)
  - Arquivos: [composer.json](../composer.json), [docs/adr/ADR-002.md](../docs/adr/ADR-002.md)
  - Evidência: Avaliação concluída e registrada em ADR-002 descartando generators em favor de operações in-memory de alta performance; palavras-chave `iterator` e `yield` removidas do `composer.json`.

- [x] **T-001 · P1 · Corrigir divergência documental e de implementação no método `toObject()`** (2026-10-13)
  - Arquivos: [README.md](../README.md), [src/LinqPHP.php](../src/LinqPHP.php), [tests/Unit/AdditionalTest.php](../tests/Unit/AdditionalTest.php)
  - Evidência: Contrato alinhado exclusivamente à propriedade `data` conforme ADR-004 e decisão do usuário; asserções de `memoryUsed` e `data` validadas em `composer test` (48 testes, 505 asserções).

## Canceladas

(nenhuma)
