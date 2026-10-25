# Contexto do Projeto: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:proposito -->
## Propósito

Biblioteca leve e de alta performance para manipulação de coleções de dados tabulares (arrays) em PHP 8.1+, sem dependências externas de produção, inspirada na sintaxe fluida do LINQ (Language Integrated Query).
<!-- discovery:end:proposito -->

<!-- discovery:start:stack -->
## Stack

| Componente | Tecnologia | Versão | Origem |
| :--- | :--- | :--- | :--- |
| Linguagem | PHP | >= 8.1 | `composer.json` (`require.php`) |
| Testes Unitarios | PHPUnit | ^13.2 | `composer.json` (`require-dev`) |
| Benchmarks | PHPBench | ^1.7 | `composer.json` (`require-dev`) |
| CI/CD | GitHub Actions | actions/checkout@v4, setup-php@v2 | `.github/workflows/ci.yml` |
| Padrao de Codigo | PSR-12 | n/d | `.vscode/settings.json`, `.editorconfig` |
<!-- discovery:end:stack -->

<!-- discovery:start:comandos -->
## Comandos

| Ação | Comando | Origem |
| :--- | :--- | :--- |
| Instalar dependências | `composer install` | `composer.json` |
| Testes unitarios | `composer test` | `composer.json` (`scripts.test`) |
| Testes detalhados | `composer test:v` | `composer.json` (`scripts.test:v`) |
| Benchmark customizado | `composer bench` | `composer.json` (`scripts.bench`) |
| Benchmark agregado | `composer perform` | `composer.json` (`scripts.perform`) |
| QA completo | `composer qa` | `composer.json` (`scripts.qa`) |
| Validar manifesto | `composer validate --strict` | `.github/workflows/ci.yml` |
| Resetar ambiente | `composer reset` | `composer.json` (`scripts.reset`) |
<!-- discovery:end:comandos -->

<!-- discovery:start:topologia -->
## Topologia de pastas

```text
.
|-- .agents/        # Artefatos de contexto e diretrizes para IAs
|-- .github/        # Workflows de CI, templates de issue e PR
|-- .vscode/        # Configurações de editor e linter PSR-12
|-- benchmarks/     # Suites e runner customizado de benchmark de performance
|-- demo/           # Exemplos praticos de execução e dashboard
|-- docs/           # Documentação de governanca e comunidade
|-- src/            # Codigo-fonte principal da biblioteca (LinqPHP.php)
`-- tests/          # Suites de testes PHPUnit (Unit e Integration)
```
<!-- discovery:end:topologia -->

<!-- discovery:start:indice -->
## Indice da documentação

| Documento | Conteudo |
| :--- | :--- |
| [AGENTS.md](./AGENTS.md) | Regras e restrições obrigatorias para assistentes de IA |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitetura, fluxo de execução e componentes |
| [DATA.md](./DATA.md) | Estrutura de dados in-memory, tipagem e semantica relacional |
| [PRD.md](./PRD.md) | Escopo funcional, personas e matriz de recursos LINQ |
| [GUIDELINE.md](./GUIDELINE.md) | Padroes de codigo, estilo PSR-12 e convenções de commit |
| [TESTING.md](./TESTING.md) | Estrategia de testes e suites de benchmarks |
| [OPERATIONS.md](./OPERATIONS.md) | CI/CD, ambientes e processos de build |
| [DECISIONS.md](./DECISIONS.md) | Registro de decisões arquiteturais retroativas |
| [TASKS.md](./TASKS.md) | Backlog e gestao de debitos tecnicos mapeados |
<!-- discovery:end:indice -->
