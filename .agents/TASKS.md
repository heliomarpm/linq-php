# Tarefas do Projeto: LinqPHP

> **Meta:** Estabilizar governança, corrigir inconsistências documentais e aprimorar qualidade contínua (CI/QA)\
> **Status:** em desenvolvimento | **Progresso:** 3/8 (37%) | **WIP:** 0/2 | **Atualizado em:** 2026-10-13

## Em progresso

(nenhuma)

## Bloqueadas

(nenhuma)

## Backlog

### Alta Prioridade

- [ ] **T-002 · P1 · Expandir matriz de versões do PHP no workflow de CI**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [.github/workflows/ci.yml](../.github/workflows/ci.yml#L18), [composer.json](../composer.json#L55)
  - DoD:
    - [ ] Adicionar PHP 8.1, 8.2 e 8.3 na matriz do GitHub Actions
    - [ ] Garantir que testes e benchmarks rodem com sucesso em todas as versões suportadas

### Média Prioridade

- [ ] **T-003 · P2 · Corrigir portabilidade e duplicação no script `reset` do Composer**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [composer.json](../composer.json#L47-L51)
  - DoD:
    - [ ] Remover chamada duplicada de `composer install`
    - [ ] Tornar o comando de limpeza portável ou compatível com ambientes Windows

- [ ] **T-004 · P2 · Corrigir metadados do projeto em `CITATION.cff`**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [CITATION.cff](../CITATION.cff)
  - DoD:
    - [ ] Substituir referências legadas de `ion-calendar` por `linq-php`
    - [ ] Atualizar resumo, palavras-chave e URL do repositório

- [ ] **T-005 · P2 · Configurar ferramenta de análise estática formal (PHPStan) no QA**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [composer.json](../composer.json), [.editorconfig](../.editorconfig)
  - DoD:
    - [ ] Adicionar `phpstan/phpstan` no `require-dev` do Composer
    - [ ] Criar script `phpstan` e integrá-lo ao comando `composer qa`
    - [ ] Garantir que o código passe sem erros no nível configurado

- [ ] **T-008 · P2 · Reescrever em português os documentos de governança e templates de issue**
  - Origem: manual | Dep.: T-004
  - Ref: [CITATION.cff](../CITATION.cff), [docs/CODE_OF_CONDUCT.md](../docs/CODE_OF_CONDUCT.md), [docs/SUPPORT.md](../docs/SUPPORT.md), [docs/CONTRIBUTING.md](../docs/CONTRIBUTING.md), [.github/ISSUE_TEMPLATE](../.github/ISSUE_TEMPLATE)
  - DoD:
    - [ ] Traduzir `CITATION.cff` mantendo compatibilidade com a especificação CFF
    - [ ] Reescrever `docs/CODE_OF_CONDUCT.md` em português (Contributor Covenant em pt-BR)
    - [ ] Reescrever `docs/SUPPORT.md` em português alinhado aos canais do LinqPHP
    - [ ] Reescrever `docs/CONTRIBUTING.md` em português detalhando fluxo de contribuição e convenções
		- [ ] Reescrever `.github/pull_request_template.md` em português
    - [ ] Traduzir todos os arquivos de `.github/ISSUE_TEMPLATE/` (`README.md`, `bug_report.yml`, `feature_request.yml`, `documentation.yml`, `support_question.yml`) e sanear referências obsoletas

### Baixa Prioridade

(nenhuma)

## Concluídas recentemente

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
