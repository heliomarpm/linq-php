# Estrategia de Testes: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:estrategia -->
## Estrategia Geral
O projeto conta com piramide de testes focada em validacao funcional exaustiva (PHPUnit) e validacao de desempenho / consumo de recursos (PHPBench e custom benchmark runner).
- **Testes Unitarios**: cobrem metodos individuais de `LinqPHP` com matriz de dados sinteticos e casos de borda (dados nulos, colecoes vazias, tipos incompativeis).
- **Testes de Integracao**: testam pipelines completos encadeando `from`, `join`, `where`, `groupBy` e `toArray`.
- **Benchmarks**: comparam tempo e alocacao de memoria com escalas de 500 a 100.000 registros.
<!-- discovery:end:estrategia -->

<!-- discovery:start:execucao -->
## Comandos de Execucao

| Tipo | Comando | Origem |
| :--- | :--- | :--- |
| Testes Unitarios e Integracao | `composer test` | `composer.json` (`scripts.test`) |
| Testes em Modo Debug | `composer test:v` | `composer.json` (`scripts.test:v`) |
| Benchmark Customizado | `composer bench` | `composer.json` (`scripts.bench`) |
| Benchmark com PHPBench | `composer perform` | `composer.json` (`scripts.perform`) |
| Bateria Completa de QA | `composer qa` | `composer.json` (`scripts.qa`) |
<!-- discovery:end:execucao -->

<!-- discovery:start:estrutura -->
## Estrutura das Suites

```text
tests/
|-- Unit/
|   |-- AdditionalTest.php   # Testes adicionais e metodos utilitarios
|   |-- DistinctTest.php     # Desduplicacao de registros
|   |-- GroupByTest.php      # Agrupamentos e operacoes de agregacao (sum, avg, count, min, max)
|   |-- JoinTest.php         # Juncoes INNER, LEFT, RIGHT e FULL
|   |-- SelectTest.php       # Projecao e selecao restrita
|   |-- UnionAllTest.php     # Concatencao de arrays
|   `-- WhereTest.php        # Operadores de comparacao e predicados customizados
`-- Integration/
    `-- LinqFlowTest.php     # Fluxo ponta a ponta integrando multiplos metodos
```
<!-- discovery:end:estrutura -->

<!-- discovery:start:lacunas -->
## Lacunas Identificadas
- **Cobertura de Codigo**: atualmente desativada no CI (`coverage: none` em `.github/workflows/ci.yml`), sem metricas de percentual de cobertura formal.
- **Validacao de Versoes Antigas no CI**: o CI executa apenas contra PHP 8.4, nao exercitando o piso de suporte especificado no composer.json (`>=8.1`).
<!-- discovery:end:lacunas -->
