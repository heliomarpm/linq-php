# Modelo de Dados: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:persistencia -->
## Persistencia
Nao ha persistencia em banco de dados ou servicos externos. Todos os dados sao mantidos e processados estritamente in-memory via estruturas nativas do PHP (`array` e `stdClass`).
<!-- discovery:end:persistencia -->

<!-- discovery:start:contratos -->
## Contratos e Estruturas de Dados

### 1. Colecao Tabular (Dataset de Entrada)
Representa uma tabela em memoria composta por uma lista indexada de arrays associativos:
- **Tipo PHP**: `array<int, array<string, mixed>>`
- **Invariante**: deve satisfazer `is_array($data)` e `array_is_list($data)`.

### 2. Especificacao de Join
- **Tipo de Juncao**: enum `JoinType` (`INNER`, `LEFT`, `RIGHT`, `FULL`).
- **Campos Relacionados**: array associativo mapeando chave esquerda => chave direita:
  - Exemplo: `['user_id' => 'id_user']`.

### 3. Especificacao de Agregacao (`groupBy`)
- **Campos de Agrupamento**: `array<string>` (colunas que definem a chave do grupo).
- **Agregacoes Suportadas**: `array<string, array<string>>` onde as chaves sao os operadores:
  - `sum`: soma dos valores numericos da coluna informada.
  - `count`: contagem de ocorrencias no grupo.
  - `avg`: media aritmetica dos valores da coluna.
  - `min`: menor valor da coluna.
  - `max`: maior valor da coluna.

### 4. Condicoes de Filtragem (`where`)
- **Filtro Simples**: tupla com operador `[campo, operador, valor]`, e.g. `['status', '=', 'ativo']`.
- **Operadores de Comparacao**: `=`, `!=`, `>`, `>=`, `<`, `<=`, `in`, `notin`, `contains`, `startswith`, `endswith`.
- **Filtro Funcional**: callable/closure `fn(array $row): bool`.

### 5. Objeto de Saida de Metricas (`toObject`)
Retorno do metodo terminal `toObject()`:
- `elapsedTime` (`float`): tempo decorrido da operacao em segundos.
- `memoryUsed` (`string`): consumo aproximado de memoria formatado em KB ou MB.
- `count` (`int`): numero de registros na colecao resultante.
- `data` (`array<int, array<string, mixed>>`): colecao resultante modificada.
- *(Nota: documentado como `rows` no README, implementado como `data` no codigo).*
<!-- discovery:end:contratos -->

<!-- discovery:start:integracoes -->
## Sistemas e Integracoes Externas
Nao ha integracoes com APIs externas, filas, caches ou servicos de terceiros. A biblioteca e autocontida.
<!-- discovery:end:integracoes -->
