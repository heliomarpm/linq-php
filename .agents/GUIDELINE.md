# Diretrizes de Engenharia: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:padroes -->
## Padroes de Codificacao e Estilo
- **Padrao PSR-12**: formatacao estrita com indentacao de 2 espacos (conforme configuracao local), quebras de linha em LF e delimitador de linhas de no maximo 160 caracteres (Origem: `.editorconfig`, `.vscode/settings.json`).
- **Nomenclatura**: metodos publicos em `camelCase` alinhados com convencoes LINQ (`where`, `groupBy`, `select`, `join`, `orderBy`); enums em `PascalCase` com valores em uppercase (`JoinType::INNER`).
- **Tipagem**: tipos declarados em assinaturas e retornos em todos os metodos publicos e protegidos; docblocks especificando generics de array (`array<int, array<string, mixed>>`).
- **Tratamento de Erros**: uso exclusivo de `InvalidArgumentException` para dados que nao cumprem os pre-requisitos de entrada, com mensagens informando o contexto do metodo violado.
<!-- discovery:end:padroes -->

<!-- discovery:start:git -->
## Convencoes Git e Contribuicao
- **Conventional Commits**: mensagens no formato `<tipo>(escopo opcional): <gitmoji> <descricao>` (Origem: `docs/CONTRIBUTING.md`, historico git e diretrizes de projeto).
  - Tipos comuns: `feat` (✨), `fix` (🐛), `refactor` (♻️), `perf` (⚡), `docs` (📝), `test` (🧪), `ci` (🚀), `build` (📦).
  - Assunto obrigatoriamente em `pt-br`, maximo de 72 caracteres, verbo no infinitivo e sem ponto final.
- **Branches e Pull Requests**: branch criada a partir de `main` no formato `feature/<nome>` ou `fix/<nome>`. Pull Requests devem preencher o checklist de `.github/workflows/pull_request_template.md`.
<!-- discovery:end:git -->

<!-- discovery:start:dependencias -->
## Politica de Dependencias
- **Zero Runtime Dependencies**: proibido adicionar pacotes na secao `require` do `composer.json`. Todo algoritmo de consulta e juncao deve ser implementado usando recursos nativos do PHP 8.1+.
- **Dependencias de Desenvolvimento**: limitadas a ferramentas de qualidade, testes e medicao de performance (`phpunit`, `phpbench`).
<!-- discovery:end:dependencias -->
