# Assessment Crítico: LinqPHP

Auditoria técnica de arquitetura, código-fonte, performance, testes e infraestrutura de CI/CD.

---

## 1. Fraquezas Críticas e Arquiteturais

### 1.1. Quebra de PSR-4: `Fatal error` no autoload direto de `JoinType`
- **Arquivo:** `src/LinqPHP.php:L10-L16`
- **Problema:** O enum `JoinType` está declarado no mesmo arquivo de `LinqPHP.php`.
- **Impacto:** O Composer mapeia `"HeliomarPM\\LinqPHP\\": "src/"`. Se um consumidor fizer `use HeliomarPM\LinqPHP\JoinType;` e instanciar `JoinType::INNER` antes de chamar `LinqPHP`, o PHP dispara:
  ```text
  Fatal error: Uncaught Error: Class "HeliomarPM\LinqPHP\JoinType" not found
  ```
- **Solução:** Extrair o enum para seu próprio arquivo `src/JoinType.php` em conformidade com PSR-1 e PSR-4.

### 1.2. Dessincronização do `composer.lock` e Falha no CI
- **Arquivos:** `composer.json`, `composer.lock`, `.github/workflows/ci.yml:L32`
- **Problema:** `composer.json` foi editado para aceitar múltiplas versões do PHPUnit (`^11.0 || ^12.0 || ^13.4`), porém o `composer.lock` não foi regerado.
- **Impacto:** O comando `composer validate --strict` falha imediatamente com código 1, travando a pipeline do GitHub Actions.
- **Solução:** Sincronizar o lockfile executando `composer update --lock`.

### 1.3. Divergência na Matriz do CI vs. Requisito PHP 8.1
- **Arquivos:** `composer.json`, `.github/workflows/ci.yml:L18`
- **Problema:** O projeto tem como diretriz base `require: php >= 8.1`. No entanto, a matriz do CI removeu `8.1` (`php: ['8.2', '8.3', '8.4']`).
- **Impacto:** Regressões incompatíveis com a sintaxe ou funções do PHP 8.1 podem passar despercebidas.
- **Solução:** Restaurar `8.1` no CI combinando testes com dependências mínimas (`--prefer-lowest`).

### 1.4. Inconsistência de Case-Insensitivity e Corrupção Multibyte em `where()`
- **Arquivo:** `src/LinqPHP.php:L373-L375`, `L1196`, `L1222-L1224`
- **Problema:** `where()` força `strtolower()` incondicionalmente em todas as strings.
- **Impacto:**
  1. Impossibilita buscas exatas (case-sensitive) para hashes, UUIDs, senhas, SKUs ou códigos.
  2. `strtolower()` nativo do PHP corrompe strings UTF-8 acentuadas (`"João"`, `"Água"`, `"Maçã"`).
- **Solução:** Suportar flag opcional `caseSensitive: true` e utilizar `mb_strtolower` com fallback nativo.

### 1.5. Risco de Colisão de Chaves Compostas no `groupBy()` com Delimitador `_`
- **Arquivo:** `src/LinqPHP.php:L458`
- **Problema:** Grupos múltiplos usam `implode('_', ...)`, enquanto `join()` usa `implode("\0", ...)`.
- **Impacto:** Valores contendo underscores (ex: `['a_b', 'c']` e `['a', 'b_c']`) colidem na mesma chave `'a_b_c'`, fundindo registros indevidamente.
- **Solução:** Unificar o uso do delimitador nulo `"\0"` em `groupBy()`.

### 1.6. Script `reset` Incompatível com Windows Nativo
- **Arquivo:** `composer.json:L65`
- **Problema:** O script define `"reset": ["rm -rf vendor composer.lock", "composer install"]`.
- **Impacto:** `rm -rf` não existe no PowerShell ou CMD do Windows, gerando erro de execução.
- **Solução:** Substituir por script PHP multiplataforma via `@php`.

---

## 2. Duplicidades Identificadas para Mesclar

### 2.1. Blocos Idênticos de Reflection e Ordenação em `orderByKey()`
- **Arquivo:** `src/LinqPHP.php:L684-L699` e `L708-L723`
- **Problema:** Repetição linha por linha da verificação de parâmetros via `ReflectionFunction` e aplicação de `usort` ou `array_multisort`.
- **Solução:** Extrair para método auxiliar privado `sortWithCallable(callable $key, bool $isDesc): void`.

### 2.2. Validação Duplicada de Comparator em `orderBy()`
- **Arquivo:** `src/LinqPHP.php:L840-L845` e `L851-L856`
- **Problema:** Validação duplicada que checa se o callable possui 2 parâmetros em ordenação multi-critério.
- **Solução:** Reutilizar método validador único.

### 2.3. Duplicação de Loop e Soma em `avg()` vs `sum()`
- **Arquivo:** `src/LinqPHP.php:L1027-L1045` e `L997-L1014`
- **Problema:** `avg()` duplica todo o loop de acumulação já implementado em `sum()`.
- **Solução:** Delegar o cálculo: `$count = count($this->data); return $count === 0 ? null : (float)($this->sum($field) / $count);`.

### 2.4. Código Duplicado nos Cenários da Demo
- **Arquivo:** `demo/index.php`
- **Problema:** Cada um dos 14 cenários define o mesmo pipeline LINQ duas vezes: como string de exibição (`'codigo'`) e como Closure executável (`'run'`).
- **Solução:** Centralizar o runner ou gerar o snippet dinamicamente para evitar descompassos.

---

## 3. Oportunidades de Performance e Memória

### 3.1. Eliminar Sobrecarga O(N) de `nullFillRightOnly()` em Joins
- **Arquivo:** `src/LinqPHP.php:L201`, `L213`, `L1167-L1177`
- **Gargalo:** Em linhas não pareadas de `LEFT`, `RIGHT` ou `FULL JOIN`, o código executa repetidamente `array_keys`, `array_diff` e `array_fill_keys`.
- **Otimização:** Pré-computar os templates de preenchimento nulo uma única vez antes dos loops.

### 3.2. Fast-Path de Chave Simples no `groupBy()`
- **Arquivo:** `src/LinqPHP.php:L458-L461`
- **Gargalo:** Agrupamentos por uma única coluna executam `array_intersect_key` duas vezes por registro, além de `implode('_', ...)`. Em 100.000 itens são 200.000 chamadas desnecessárias.
- **Otimização:** Fast-path para `count($groupingFields) === 1`, obtendo a chave diretamente via `$item[$field]`.

### 3.3. Acumulação O(1) de Memória no `avg` dentro de `groupBy()`
- **Arquivo:** `src/LinqPHP.php:L1311`
- **Gargalo:** Coleta todos os números em arrays auxiliares (`$groupedResult[$groupKey][$fieldName][]`), alocando O(N) memória para processar a média no final.
- **Otimização:** Acumular soma e quantidade diretamente em acumuladores escalares O(1).

### 3.4. Eliminação de Alocações Desnecessárias de Arrays no `select()`
- **Arquivo:** `src/LinqPHP.php:L537`
- **Gargalo:** Uso de `array_replace($nullTemplate, array_intersect_key(...))` aloca dois arrays intermediários para cada linha da coleção.
- **Otimização:** Construção direta com `foreach` simples por coluna selecionada.

### 3.5. Fast-Path no `unionAll()` para Esquemas Idênticos
- **Arquivo:** `src/LinqPHP.php:L105-L125`
- **Gargalo:** Varre todas as chaves de todas as linhas de ambas as coleções antes de normalizar, mesmo quando as colunas já são idênticas.
- **Otimização:** Detecção rápida do esquema da primeira linha para executar `array_merge` direto.
