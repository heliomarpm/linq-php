# Diretrizes para Assistentes de IA

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:regras -->
## Regras obrigatórias

1. Zero dependências em produção: qualquer novo recurso deve usar exclusivamente PHP nativo (Origem: `composer.json`, `require` contem apenas `php >= 8.1`).
2. Compatibilidade PHP 8.1+: utilizar apenas recursos compatíveis com PHP 8.1 (enums nativos, first-class callables, `array_is_list`).
3. Interface fluida (method chaining): metodos de transformação devem retornar `$this` (`self`), com exceção dos metodos de terminalização `toArray()` e `toObject()`.
4. Padrao de codificação PSR-12: seguir estritamente as regras de formatação e estilo (Origem: `.vscode/settings.json`, `.editorconfig`).
5. Tratamento de exceções com `InvalidArgumentException`: entradas invalidas devem disparar exceções nativas especificas (Origem: `src/LinqPHP.php`).
6. Convenção de commits: seguir Conventional Commits com Gitmoji compativel, assunto em `pt-br` de no maximo 72 caracteres, verbo no infinitivo e sem pontuação final (Origem: historico git e diretrizes globais).

## Restrições (o que não fazer)

- Não adicionar pacotes externos na seção `require` do `composer.json`.
- Não quebrar retrocompatibilidade das assinaturas publicas de `LinqPHP` ou do enum `JoinType`.
- Não utilizar funções obsoletas ou construções não compatíveis com PHP 8.1.
- Não comitar codigo que cause falhas na suite de testes (`composer test`).
- Não introduzir dependências de framework (e.g. Laravel Collection ou Symfony).

## Skills aplicaveis

- `-tech-php`: Melhores práticas em PHP moderno 8.1+, tipagem estrita e PSRs.
- `-testing`: Manutenção e expansão da suite de testes com PHPUnit 13.
- `-git`: Higiene de commits convencionais e fluxo de branches.

## Decisões críticas

- [ADR-001: Zero Dependências Externas em Produção](../docs/adr/ADR-001.md)
- [ADR-002: Interface Fluida com Mutação In-Memory](../docs/adr/ADR-002.md)
- [ADR-003: Enum Nativo JoinType](../docs/adr/ADR-003.md)
- [ADR-004: Telemetria de Execução Embutida](../docs/adr/ADR-004.md)

## Antes de concluir uma tarefa

Executar obrigatoriamente a suite de validação:

1. `composer test` (garantir 100% de testes passando).
2. `composer bench` (validar que não houve degradação crítica de performance).
<!-- discovery:end:regras -->

<!-- manual:start -->
<!-- Notas e regras escritas pela equipe. A skill não altera este bloco. -->
<!-- manual:end -->
