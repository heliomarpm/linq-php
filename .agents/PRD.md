# Product Requirements Document (PRD Retroativo): LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:proposito -->
## Problema e Proposta de Valor
- **Problema**: manipulacao de matrizes de dados multidimensionais em PHP frequentemente exige codigo imperativo verboso (`foreach`, `array_filter`, `array_map`, `usort`), resultando em codigo de dificil leitura e manutencao.
- **Proposta de Valor**: oferecer uma biblioteca leve, com sintaxe concisa e declarativa inspirada no LINQ (.NET), permitindo consultas, juncoes relacionais, filtros complexos e agregacoes em colecoes tabulares com zero dependencias externas e alta performance.
<!-- discovery:end:proposito -->

<!-- discovery:start:personas -->
## Publico-alvo e Personas (inferido)
1. **Engenheiro de Backend PHP**: necessita processar, transformar e normalizar relatorios e cargas de dados em memoria antes de serializar para APIs JSON.
2. **Desenvolvedor Legado / Migracao**: precisa desacoplar transformacoes de dados de queries SQL pesadas ou aplicar juncoes relacionais sobre dados de origens distintas (e.g. CSV + API REST).
<!-- discovery:end:personas -->

<!-- discovery:start:funcionalidades -->
## Funcionalidades Existentes

| Funcionalidade | Status | Origem |
| :--- | :--- | :--- |
| Criacao de Colecao (`from`) | Implementada | `src/LinqPHP.php:L53` |
| Filtros Relacionais e Funcionais (`where`) | Implementada | `src/LinqPHP.php:L330` |
| Juncoes Relacionais (`join`: INNER, LEFT, RIGHT, FULL) | Implementada | `src/LinqPHP.php:L198` |
| Agrupamentos e Metricas (`groupBy`: sum, avg, min, max, count) | Implementada | `src/LinqPHP.php:L430` |
| Projecao de Campos (`select` com modo estrito) | Implementada | `src/LinqPHP.php:L581` |
| Eliminacao de Duplicidades (`distinct`) | Implementada | `src/LinqPHP.php:L630` |
| Paginacao e Limite (`take`) | Implementada | `src/LinqPHP.php:L669` |
| Ordenacao Simples e Composta (`orderBy`, `orderByKey`) | Implementada | `src/LinqPHP.php:L699` |
| Concatenacao de Listas (`unionAll`) | Implementada | `src/LinqPHP.php:L113` |
| Exportacao Tabular (`toArray`) | Implementada | `src/LinqPHP.php:L752` |
| Exportacao com Telemetria (`toObject`) | Implementada | `src/LinqPHP.php:L771` |
| Suite de Benchmarks Integrada | Implementada | `benchmarks/run.php`, `phpbench.json` |
| Painel Interativo de Demonstracao | Implementada | `demo/dashboard.php` |
<!-- discovery:end:funcionalidades -->

<!-- discovery:start:fora_escopo -->
## Fora de Escopo Observado
- Conexao direta com bancos de dados relacionais (SQL/PDO) ou provedores NoSQL.
- Operacoes assincronas ou baseadas em Generators/Yield com lazy evaluation [A CONFIRMAR].
- Suporte a dados heterogeneos que nao sejam listas indexadas de arrays associativos.
<!-- discovery:end:fora_escopo -->

<!-- discovery:start:lacunas -->
## Lacunas e Perguntas Abertas
- **Divergencia `toObject()`**: o README documenta `$obj->rows`, mas a propriedade gerada no codigo e `$obj->data`.
- **Avaliacao Preguicosa (Lazy Evaluation)**: embora a palavra-chave `yield` esteja no `composer.json`, as operacoes atuais executam em arrays ansiosos (eager). Ha plano para suporte a iterators/generators? [A CONFIRMAR]
- **Metricas de Sucesso**: tempos maximos aceitaveis por operacao em relacao a implementacoes nativas puras [A CONFIRMAR].
<!-- discovery:end:lacunas -->
