# Estrategia de Testes: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:estrategia -->
## Estrategia Geral

O projeto conta com piramide de testes focada em validação funcional exaustiva (PHPUnit) e validação de desempenho / consumo de recursos (PHPBench e custom benchmark runner).

- **Testes Unitarios**: cobrem metodos individuais de `LinqPHP` com matriz de dados sinteticos e casos de borda (dados nulos, coleções vazias, tipos incompatíveis).
- **Testes de Integração**: testam pipelines completos encadeando `from`, `join`, `where`, `groupBy` e `toArray`.
- **Benchmarks**: comparam tempo e alocação de memoria com escalas de 500 a 100.000 registros.
<!-- discovery:end:estrategia -->

<!-- discovery:start:execução -->
## Comandos de Execução

| Tipo | Comando | Origem |
| :--- | :--- | :--- |
| Testes Unitarios e Integração | `composer test` | `composer.json` (`scripts.test`) |
| Testes em Modo Debug | `composer test:v` | `composer.json` (`scripts.test:v`) |
| Benchmark Customizado | `composer bench` | `composer.json` (`scripts.bench`) |
| Benchmark com PHPBench | `composer perform` | `composer.json` (`scripts.perform`) |
| Bateria Completa de QA | `composer qa` | `composer.json` (`scripts.qa`) |
<!-- discovery:end:execução -->

<!-- discovery:start:estrutura -->
## Estrutura das Suites

```text
tests/
|-- Unit/
|   |-- AdditionalTest.php   # Testes adicionais e metodos utilitarios
|   |-- DistinctTest.php     # Desduplicação de registros
|   |-- GroupByTest.php      # Agrupamentos e operações de agregação (sum, avg, count, min, max)
|   |-- JoinTest.php         # Junções INNER, LEFT, RIGHT e FULL
|   |-- OrderByTest.php      # Ordenação de coleções
|   |-- SelectTest.php       # Projeção e seleção restrita
|   |-- UnionAllTest.php     # Concatenção de arrays
|   `-- WhereTest.php        # Operadores de comparação e predicados customizados
`-- Integration/
    `-- LinqFlowTest.php     # Fluxo ponta a ponta integrando multiplos metodos
```
<!-- discovery:end:estrutura -->

<!-- discovery:start:lacunas -->
## Lacunas Identificadas

- **Cobertura de Codigo**: atualmente desativada no CI (`coverage: none` em `.github/workflows/ci.yml`), sem metricas de percentual de cobertura formal.
- **Validação de Versões Antigas no CI**: o CI executa apenas contra PHP 8.4, não exercitando o piso de suporte especificado no composer.json (`>=8.1`).
<!-- discovery:end:lacunas -->
