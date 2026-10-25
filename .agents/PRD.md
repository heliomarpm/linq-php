# Product Requirements Document (PRD Retroativo): LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:proposito -->
## Problema e Proposta de Valor

- **Problema**: manipulação de matrizes de dados multidimensionais em PHP frequentemente exige codigo imperativo verboso (`foreach`, `array_filter`, `array_map`, `usort`), resultando em codigo de dificil leitura e manutenção.
- **Proposta de Valor**: oferecer uma biblioteca leve, com sintaxe concisa e declarativa inspirada no LINQ (.NET), permitindo consultas, junções relacionais, filtros complexos e agregações em coleções tabulares com zero dependências externas e alta performance.
<!-- discovery:end:proposito -->

<!-- discovery:start:personas -->
## Publico-alvo e Personas (inferido)

1. **Engenheiro de Backend PHP**: necessita processar, transformar e normalizar relatorios e cargas de dados em memoria antes de serializar para APIs JSON.
2. **Desenvolvedor Legado / Migração**: precisa desacoplar transformações de dados de queries SQL pesadas ou aplicar junções relacionais sobre dados de origens distintas (e.g. CSV + API REST).
<!-- discovery:end:personas -->

<!-- discovery:start:funcionalidades -->
## Funcionalidades Existentes

| Funcionalidade | Status | Origem |
| :--- | :--- | :--- |
| Criação de Coleção (`from`) | Implementada | `src/LinqPHP.php:L53` |
| Filtros Relacionais e Funcionais (`where`) | Implementada | `src/LinqPHP.php:L330` |
| Junções Relacionais (`join`: INNER, LEFT, RIGHT, FULL) | Implementada | `src/LinqPHP.php:L198` |
| Agrupamentos e Metricas (`groupBy`: sum, avg, min, max, count) | Implementada | `src/LinqPHP.php:L430` |
| Projeção de Campos (`select` com modo estrito) | Implementada | `src/LinqPHP.php:L581` |
| Eliminação de Duplicidades (`distinct`) | Implementada | `src/LinqPHP.php:L630` |
| Paginação e Limite (`take`) | Implementada | `src/LinqPHP.php:L669` |
| Ordenação Simples e Composta (`orderBy`, `orderByKey`) | Implementada | `src/LinqPHP.php:L699` |
| Concatenação de Listas (`unionAll`) | Implementada | `src/LinqPHP.php:L113` |
| Exportação Tabular (`toArray`) | Implementada | `src/LinqPHP.php:L752` |
| Exportação com Telemetria (`toObject`) | Implementada | `src/LinqPHP.php:L771` |
| Suite de Benchmarks Integrada | Implementada | `benchmarks/run.php`, `phpbench.json` |
| Painel Interativo de Demonstração | Implementada | `demo/dashboard.php` |
<!-- discovery:end:funcionalidades -->

<!-- discovery:start:fora_escopo -->
## Fora de Escopo Observado

- Conexao direta com bancos de dados relacionais (SQL/PDO) ou provedores NoSQL.
- Operações assincronas ou baseadas em Generators/Yield com lazy evaluation [A CONFIRMAR].
- Suporte a dados heterogeneos que não sejam listas indexadas de arrays associativos.
<!-- discovery:end:fora_escopo -->

<!-- discovery:start:lacunas -->
## Lacunas e Perguntas Abertas

- **Divergencia `toObject()`**: o README documenta `$obj->rows`, mas a propriedade gerada no codigo e `$obj->data`.
- **Avaliação Preguicosa (Lazy Evaluation)**: embora a palavra-chave `yield` esteja no `composer.json`, as operações atuais executam em arrays ansiosos (eager). Ha plano para suporte a iterators/generators? [A CONFIRMAR]
- **Metricas de Sucesso**: tempos maximos aceitaveis por operação em relação a implementações nativas puras [A CONFIRMAR].
<!-- discovery:end:lacunas -->
