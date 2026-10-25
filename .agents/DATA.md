# Modelo de Dados: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:persistencia -->
## Persistência

Não há persistência em banco de dados ou servicos externos. Todos os dados são mantidos e processados estritamente in-memory via estruturas nativas do PHP (`array` e `stdClass`).
<!-- discovery:end:persistencia -->

<!-- discovery:start:contratos -->
## Contratos e Estruturas de Dados

### 1. Coleção Tabular (Dataset de Entrada)

Representa uma tabela em memoria composta por uma lista indexada de arrays associativos:

- **Tipo PHP**: `array<int, array<string, mixed>>`
- **Invariante**: deve satisfazer `is_array($data)` e `array_is_list($data)`.

### 2. Especificação de Join

- **Tipo de Junção**: enum `JoinType` (`INNER`, `LEFT`, `RIGHT`, `FULL`).
- **Campos Relacionados**: array associativo mapeando chave esquerda => chave direita:
  - Exemplo: `['user_id' => 'id_user']`.

### 3. Especificação de Agregação (`groupBy`)

- **Campos de Agrupamento**: `array<string>` (colunas que definem a chave do grupo).
- **Agregações Suportadas**: `array<string, array<string>>` onde as chaves são os operadores:
  - `sum`: soma dos valores numericos da coluna informada.
  - `count`: contagem de ocorrencias no grupo.
  - `avg`: media aritmetica dos valores da coluna.
  - `min`: menor valor da coluna.
  - `max`: maior valor da coluna.

### 4. Condições de Filtragem (`where`)

- **Filtro Simples**: tupla com operador `[campo, operador, valor]`, e.g. `['status', '=', 'ativo']`.
- **Operadores de Comparação**: `=`, `!=`, `>`, `>=`, `<`, `<=`, `in`, `notin`, `contains`, `startswith`, `endswith`.
- **Filtro Funcional**: callable/closure `fn(array $row): bool`.

### 5. Objeto de Saida de Metricas (`toObject`)

Retorno do metodo terminal `toObject()`:

- `elapsedTime` (`float`): tempo decorrido da operação em segundos.
- `memoryUsed` (`string`): consumo aproximado de memoria formatado em KB ou MB.
- `count` (`int`): numero de registros na coleção resultante.
- `data` (`array<int, array<string, mixed>>`): coleção resultante modificada.
- *(Nota: documentado como `rows` no README, implementado como `data` no codigo).*
<!-- discovery:end:contratos -->

<!-- discovery:start:integrações -->
## Sistemas e Integrações Externas

Não ha integrações com APIs externas, filas, caches ou servicos de terceiros. A biblioteca e autocontida.
<!-- discovery:end:integrações -->
