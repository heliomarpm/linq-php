# Diretrizes para Assistentes de IA

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:regras -->
## Regras obrigatorias
1. Zero dependencias em producao: qualquer novo recurso deve usar exclusivamente PHP nativo (Origem: `composer.json`, `require` contem apenas `php >= 8.1`).
2. Compatibilidade PHP 8.1+: utilizar apenas recursos compativeis com PHP 8.1 (enums nativos, first-class callables, `array_is_list`).
3. Interface fluida (method chaining): metodos de transformacao devem retornar `$this` (`self`), com excecao dos metodos de terminalizacao `toArray()` e `toObject()`.
4. Padrao de codificacao PSR-12: seguir estritamente as regras de formatacao e estilo (Origem: `.vscode/settings.json`, `.editorconfig`).
5. Tratamento de excecoes com `InvalidArgumentException`: entradas invalidas devem disparar excecoes nativas especificas (Origem: `src/LinqPHP.php`).
6. Convencao de commits: seguir Conventional Commits com Gitmoji compativel, assunto em `pt-br` de no maximo 72 caracteres, verbo no infinitivo e sem pontuacao final (Origem: historico git e diretrizes globais).

## Restricoes (o que nao fazer)
- Nao adicionar pacotes externos na secao `require` do `composer.json`.
- Nao quebrar retrocompatibilidade das assinaturas publicas de `LinqPHP` ou do enum `JoinType`.
- Nao utilizar funcoes obsoletas ou construcoes nao compativeis com PHP 8.1.
- Nao comitar codigo que cause falhas na suite de testes (`composer test`).
- Nao introduzir dependencias de framework (e.g. Laravel Collection ou Symfony).

## Skills aplicaveis
- `-tech-php`: Melhores praticas em PHP moderno 8.1+, tipagem estrita e PSRs.
- `-testing`: Manutencao e expansao da suite de testes com PHPUnit 13.
- `-git`: Higiene de commits convencionais e fluxo de branches.

## Decisoes criticas
- [ADR-001: Zero Dependencias Externas em Producao](../docs/adr/ADR-001.md)
- [ADR-002: Interface Fluida com Mutacao In-Memory](../docs/adr/ADR-002.md)
- [ADR-003: Enum Nativo JoinType](../docs/adr/ADR-003.md)
- [ADR-004: Telemetria de Execucao Embutida](../docs/adr/ADR-004.md)

## Antes de concluir uma tarefa
Executar obrigatoriamente a suite de validacao:
1. `composer test` (garantir 100% de testes passando).
2. `composer bench` (validar que nao houve degradacao critica de performance).
<!-- discovery:end:regras -->

<!-- manual:start -->
<!-- Notas e regras escritas pela equipe. A skill nao altera este bloco. -->
<!-- manual:end -->
