# Tarefas do Projeto: LinqPHP

> **Meta:** Estabilizar governança, corrigir inconsistências documentais e aprimorar recursos de consulta fluente (LINQ)\
> **Status:** concluído | **Progresso:** 11/11 (100%) | **WIP:** 0/2 | **Atualizado em:** 2026-10-24

## Em progresso

(nenhuma)

## Bloqueadas

(nenhuma)

## Backlog

### Alta Prioridade

(nenhuma)

### Média Prioridade

(nenhuma)

### Baixa Prioridade

(nenhuma)

## Concluídas recentemente

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
