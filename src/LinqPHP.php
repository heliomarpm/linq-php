<?php

namespace HeliomarPM\LinqPHP;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use stdClass;

class LinqPHP
{
    private array $data;
    private readonly float $startTime;
    private readonly int $startMemory;

    public function __construct(array $data)
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->setData($data);
    }

    /**
     * Cria uma nova instância da classe usando os dados fornecidos.
     *
     * @param array<int, array<string, mixed>> $data Os dados para inicializar o objeto.
     * @return LinqPHP O objeto recém-criado.
     *
     * * Exemplo de uso:
     *
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Jane'],
     * ];
     *
     * $linq = LinqPHP::from($data);
     *
     * Resultado: [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Jane'],
     * ]
     * ```
     */
    public static function from(array $data): self
    {
        return new self($data);
    }


    /**
     * Concatena dois arrays em um único array preservando todos os elementos e chaves.
     *
     * @param array<int, array<string, mixed>>$unionData O array a ser concatenado com o array atual.
     * @return LinqPHP Retorna a instância atual da classe.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Jane'],
     * ];
     *
     * $array2 = [
     *     ['id' => 1, 'age' => 25],
     *     ['id' => 3, 'age' => 30],
     * ];
     *
     * $result = LinqPHP::from($data)->unionAll($array2)->toArray();
     *
     * Resultado: [
     *     ['id' => 1, 'name' => 'John', 'age' => null],
     *     ['id' => 1, 'name' => null, 'age' => 25],
     *     ['id' => 2, 'name' => 'Jane', 'age' => null],
     *     ['id' => 3, 'name' => null, 'age' => 30],
     * ]
     * ```
     */
    public function unionAll(array $unionData): self
    {
        if ($this->data === []) {
            $this->data = $this->normalizeCollection($unionData);
            return $this;
        }

        if ($unionData === []) {
            return $this;
        }

        $unionData = $this->normalizeCollection($unionData);

        // Fast-path: verificar se ambas as coleções possuem o mesmo esquema de chaves
        $firstLeft = reset($this->data);
        $firstRight = reset($unionData);

        if (is_array($firstLeft) && is_array($firstRight)) {
            $leftKeys = array_keys($firstLeft);
            $rightKeys = array_keys($firstRight);

            if ($leftKeys === $rightKeys && $leftKeys !== []) {
                $keyCount = count($leftKeys);
                $isIdentical = true;

                // Verificação rápida de homogeneidade em ambas as coleções
                foreach ($this->data as $row) {
                    if (!is_array($row) || count($row) !== $keyCount || array_keys($row) !== $leftKeys) {
                        $isIdentical = false;
                        break;
                    }
                }

                if ($isIdentical) {
                    foreach ($unionData as $row) {
                        if (!is_array($row) || count($row) !== $keyCount || array_keys($row) !== $leftKeys) {
                            $isIdentical = false;
                            break;
                        }
                    }
                }

                if ($isIdentical) {
                    $this->data = array_merge($this->data, $unionData);
                    return $this;
                }
            }
        }

        // Caminho heterogêneo: coleta todas as chaves existentes
        $allKeys = [];

        foreach ($this->data as $row) {
            foreach ($row as $key => $_) {
                $allKeys[$key] = true;
            }
        }

        foreach ($unionData as $row) {
            foreach ($row as $key => $_) {
                $allKeys[$key] = true;
            }
        }

        // Pré-computa o molde nulo uma única vez (eliminando alocações repetitivas por linha)
        $nullTemplate = array_fill_keys(array_keys($allKeys), null);
        $result = [];

        foreach ($this->data as $row) {
            $result[] = array_replace($nullTemplate, $row);
        }

        foreach ($unionData as $row) {
            $result[] = array_replace($nullTemplate, $row);
        }

        $this->data = $result;
        return $this;
    }

    /**
     * Junta o array atual com outro array com base em campos relacionados.
     *
     * @param array<int, array<string, mixed>> $joinData O array a ser unido.
     * @param JoinType $joinType O tipo de união a ser realizada. Os valores válidos são "INNER", "LEFT", "RIGHT" ou "FULL". O padrão é "INNER".
     * @param array<int|string, string> $relatedFields Os campos usados para determinar a relação entre os arrays. O padrão é um array vazio.
     * @return LinqPHP O objeto atual com o array unido.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'id_course' => 1],
     *     ['id' => 2, 'name' => 'Jane', 'id_course' => 1],
     * ];
     *
     * $array2 = [
     *     ['id' => 1, 'age' => 25, 'course_id' => 1],
     *     ['id' => 1, 'age' => 25, 'course_id' => 2],
     *     ['id' => 3, 'age' => 30, 'course_id' => 1],
     * ];
     *
     * $result = LinqPHP::from($data)->join($array2, JoinType::INNER, ['id', 'id_course'=>'course_id'])->toArray();;
     *
     * Resultado: [
     *     ['id' => 1, 'name' => 'John', 'id_course' => 1, 'age' => 25, 'course_id' => 1],
     * ]
     * ```
     */
    public function join(array $joinData, JoinType $joinType, array $relatedFields): self
    {
        // Normaliza: ['id', 'x' => 'y'] → ['id'=>'id','x'=>'y']
        $leftFields = [];
        $rightFields = [];

        foreach ($relatedFields as $l => $r) {
            if (is_int($l)) {
                $leftFields[] = $r;
                $rightFields[] = $r;
            } else {
                $leftFields[] = $l;
                $rightFields[] = $r;
            }
        }

        // ============================
        // Indexa RIGHT (Fast Path para chave simples)
        // ============================
        $singleRight = count($rightFields) === 1 ? $rightFields[0] : null;
        $rightIndex = [];
        foreach ($joinData as $r) {
            $key = $singleRight !== null ? (string)($r[$singleRight] ?? '') : $this->buildCompositeKey($r, $rightFields);
            $rightIndex[$key][] = $r;
        }

        $result = [];
        $matchedRightKeys = [];
        $singleLeft = count($leftFields) === 1 ? $leftFields[0] : null;

        $isLeftOrFull = in_array($joinType, [JoinType::LEFT, JoinType::FULL], true);
        $isRightOrFull = in_array($joinType, [JoinType::RIGHT, JoinType::FULL], true);

        $rightKeys = ($isLeftOrFull || $isRightOrFull) && $joinData !== [] ? array_keys($joinData[0]) : [];
        $leftKeysSample = ($isLeftOrFull || $isRightOrFull) && $this->data !== [] ? array_keys($this->data[0]) : [];
        $rightKeysCount = count($rightKeys);
        $leftKeysCount = count($leftKeysSample);

        $rightNullTemplate = $isLeftOrFull && $joinData !== []
            ? array_fill_keys(array_diff($rightKeys, $leftKeysSample), null)
            : [];
        $leftNullTemplate = $isRightOrFull && $this->data !== []
            ? array_fill_keys(array_diff($leftKeysSample, $rightKeys), null)
            : [];

        // ============================
        // INNER / LEFT / FULL
        // ============================
        foreach ($this->data as $l) {
            $key = $singleLeft !== null ? (string)($l[$singleLeft] ?? '') : $this->buildCompositeKey($l, $leftFields);

            if (isset($rightIndex[$key])) {
                foreach ($rightIndex[$key] as $r) {
                    $result[] = array_merge($l, $r);
                }
                $matchedRightKeys[$key] = true;
            } elseif ($isLeftOrFull) {
                $nullFill = count($l) === $leftKeysCount
                    ? $rightNullTemplate
                    : $this->nullFillRightOnly($l, $joinData);
                $result[] = array_merge($l, $nullFill);
            }
        }

        // ============================
        // RIGHT / FULL
        // ============================
        if ($isRightOrFull) {
            foreach ($joinData as $r) {
                $key = $singleRight !== null ? (string)($r[$singleRight] ?? '') : $this->buildCompositeKey($r, $rightFields);

                if (!isset($matchedRightKeys[$key])) {
                    $nullFill = count($r) === $rightKeysCount
                        ? $leftNullTemplate
                        : $this->nullFillRightOnly($r, $this->data);
                    $result[] = array_merge($nullFill, $r);
                }
            }
        }

        $this->data = $result;
        return $this;
    }

    /**
     * Realiza uma união do tipo LEFT JOIN com outro array com base em campos relacionados.
     * Preserva todos os registros da coleção atual e preenche colunas ausentes do array unido com null.
     *
     * @param array<int, array<string, mixed>> $joinData O array a ser unido.
     * @param array<int|string, string> $relatedFields Os campos usados para determinar a relação entre os arrays.
     * @return LinqPHP O objeto atual com o array unido.
     *
     * * Exemplo de uso:
     * ```php
     * $result = LinqPHP::from($users)
     *     ->leftJoin($courses, ['course_id' => 'id_course'])
     *     ->toArray();
     * ```
     */
    public function leftJoin(array $joinData, array $relatedFields): self
    {
        return $this->join($joinData, JoinType::LEFT, $relatedFields);
    }

    /**
     * Realiza uma união do tipo RIGHT JOIN com outro array com base em campos relacionados.
     * Preserva todos os registros do array da direita e preenche colunas ausentes da coleção atual com null.
     *
     * @param array<int, array<string, mixed>> $joinData O array a ser unido.
     * @param array<int|string, string> $relatedFields Os campos usados para determinar a relação entre os arrays.
     * @return LinqPHP O objeto atual com o array unido.
     *
     * * Exemplo de uso:
     * ```php
     * $result = LinqPHP::from($users)
     *     ->rightJoin($courses, ['course_id' => 'id_course'])
     *     ->toArray();
     * ```
     */
    public function rightJoin(array $joinData, array $relatedFields): self
    {
        return $this->join($joinData, JoinType::RIGHT, $relatedFields);
    }

    /**
     * Realiza uma união do tipo FULL JOIN com outro array com base em campos relacionados.
     * Retorna todos os registros de ambos os arrays, preenchendo correspondências ausentes com null.
     *
     * @param array<int, array<string, mixed>> $joinData O array a ser unido.
     * @param array<int|string, string> $relatedFields Os campos usados para determinar a relação entre os arrays.
     * @return LinqPHP O objeto atual com o array unido.
     *
     * * Exemplo de uso:
     * ```php
     * $result = LinqPHP::from($users)
     *     ->fullJoin($courses, ['course_id' => 'id_course'])
     *     ->toArray();
     * ```
     */
    public function fullJoin(array $joinData, array $relatedFields): self
    {
        return $this->join($joinData, JoinType::FULL, $relatedFields);
    }

    /**
     * Realiza uma união do tipo INNER JOIN com outro array com base em campos relacionados.
     * Retorna apenas os registros que possuem correspondência em ambas as coleções.
     *
     * @param array<int, array<string, mixed>> $joinData O array a ser unido.
     * @param array<int|string, string> $relatedFields Os campos usados para determinar a relação entre os arrays.
     * @return LinqPHP O objeto atual com o array unido.
     *
     * * Exemplo de uso:
     * ```php
     * $result = LinqPHP::from($users)
     *     ->innerJoin($courses, ['course_id' => 'id_course'])
     *     ->toArray();
     * ```
     */
    public function innerJoin(array $joinData, array $relatedFields): self
    {
        return $this->join($joinData, JoinType::INNER, $relatedFields);
    }

    /**
     * Filtra a coleção de dados aceitando arrays de condições estruturadas, operadores relacionais ou closures customizadas.
     *
     * Operadores suportados nas tuplas de condição `['campo', 'operador', $valor]`:
     * - Igualdade e Desigualdade:
     *   - `=`, `==`: Igualdade ampla (`$item == $valor`).
     *   - `===`: Igualdade estrita por tipo e valor (`$item === $valor`).
     *   - `!=`, `<>`: Desigualdade ampla (`$item != $valor`).
     *   - `!==`: Desigualdade estrita (`$item !== $valor`).
     * - Comparações Relacionais e Numéricas:
     *   - `>`, `>=`: Maior que, maior ou igual a.
     *   - `<`, `<=`: Menor que, menor ou igual a.
     * - Conjuntos e Listas (Otimizados em O(1) com hashmap):
     *   - `in`: Pertence ao array fornecido (indexado via hashmap O(1) com checagem estrita de tipo).
     *   - `not in`: Não pertence ao array fornecido (negação estrita O(1)).
     * - Intervalos:
     *   - `between`: Valor contido no intervalo fechado `[$min, $max]` (inclusivo: `$min <= $item && $item <= $max`).
     *   - `not between`: Valor fora do intervalo `[$min, $max]` (exclusivo: `$item < $min || $item > $max`).
     * - Busca em Strings:
     *   - `startswith`: String inicia com o prefixo especificado.
     *   - `endswith`: String termina com o sufixo especificado.
     *   - `contains`: String contém a substring especificada.
     *
     * @param array|callable $conditions Condição única `['campo', 'op', $valor]`, closure `fn($item) => bool`, ou array contendo múltiplas condições/closures.
     * @param bool $caseSensitive Define se comparações textuais diferenciam maiúsculas/minúsculas (padrão: false, com normalização UTF-8).
     * @return self A própria instância fluente com os dados filtrados.
     *
     * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London', 'price' => 50.0],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York', 'price' => 120.0],
     *     ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris', 'price' => 200.0],
     * ];
     *
     * // Intervalos com between e exclusões com not in
     * $result = LinqPHP::from($data)
     *     ->where([
     *         ['price', 'between', [40.0, 150.0]],
     *         ['status', 'not in', ['inactive', 'banned']],
     *         ['name', 'startswith', 'J'],
     *     ])
     *     ->toArray();
     *
     * // Desigualdade e comparações estritas
     * $result = LinqPHP::from($data)
     *     ->where([
     *         ['status', '!=', 'inactive'],
     *         ['age', '>=', 25],
     *     ])
     *     ->toArray();
     *
     * // Usando Closures customizadas
     * $result = LinqPHP::from($data)
     *     ->where(fn($item) => $item['id'] % 2 !== 0)
     *     ->toArray();
     *
     * // Condição simples direta em array
     * $result = LinqPHP::from($data)
     *     ->where(['age', '>', 30])
     *     ->toArray();
     * ```
     */
    public function where(array|callable $conditions, bool $caseSensitive = false): self
    {
        // Fast-path 1: Se for uma closure/função única, executa direto sem wrapper
        if (is_callable($conditions) && !is_array($conditions)) {
            $this->data = array_values(array_filter($this->data, $conditions));
            return $this;
        }

        $normalizedConditions = $this->normalizeWhereConditions($conditions, $caseSensitive);

        // Fast-path 2: Condição única estruturada (ex: ['status', '=', 'concluido'])
        if (count($normalizedConditions) === 1 && is_array($normalizedConditions[0]) && count($normalizedConditions[0]) === 3) {
            $cond = $normalizedConditions[0];
            $key = $cond[0];
            $operator = $cond[1];
            $value = $cond[2];

            $filtered = [];
            foreach ($this->data as $item) {
                $itemValue = $item[$key] ?? null;
                if (!$caseSensitive && is_string($itemValue)) {
                    $itemValue = $this->toLower($itemValue);
                }
                if ($this->evaluateOperatorMatch($itemValue, $operator, $value)) {
                    $filtered[] = $item;
                }
            }

            $this->data = $filtered;
            return $this;
        }

        $resultadosFiltrados = array_filter(
            $this->data,
            fn(array $item): bool => $this->matchesConditions($item, $normalizedConditions, $caseSensitive)
        );

        $this->data = array_values($resultadosFiltrados);
        return $this;
    }

    /**
     * Agrupa os dados com base nos campos especificados e realiza agregações (sum, count, max, min, avg).
     *
     * @param array<int, string> $groupingFields Os campos para agrupar os dados.
     * @param array<string, array<int, string>> $aggregations As agregações a serem realizadas nos dados.
     *
     * @return LinqPHP A instância atual da classe.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
     *     ['id' => 3, 'name' => 'John', 'age' => 35],
     * ];
     *
     * $groupFields = ['name'];
     * $aggregations = ['sum' => ['age']];
     *
     * $result = LinqPHP::from($data)->groupBy($groupFields, $aggregations)->toArray();;
     * ```
     */
    public function groupBy(array $groupingFields, array $aggregations = []): self
    {
        if ($this->data === []) {
            return $this;
        }

        $groupedResult = [];
        $hasAggregations = $aggregations !== [];
        $isGlobal = ($groupingFields === []);
        $compiledAggregations = [];
        $hasAvg = false;
        $avgAccumulators = [];

        if ($hasAggregations) {
            $allAggFields = [];
            foreach ($aggregations as $fields) {
                if (is_array($fields)) {
                    foreach ($fields as $field) {
                        $allAggFields[] = $field;
                    }
                }
            }
            $valueCounts = array_count_values($allAggFields);
            $duplicatedFields = array_keys(array_filter($valueCounts, fn(int $count): bool => $count > 1));

            foreach ($aggregations as $operation => $fields) {
                $op = strtolower($operation);
                if ($op === 'avg') {
                    $hasAvg = true;
                }
                if (is_array($fields)) {
                    foreach ($fields as $field) {
                        $fieldName = in_array($field, $duplicatedFields, true)
                            ? $field . "_({$op})"
                            : $field;
                        $compiledAggregations[] = [
                            'op'        => $op,
                            'field'     => $field,
                            'fieldName' => $fieldName,
                        ];
                    }
                }
            }
        }

        if ($isGlobal) {
            // Fast-path para Totalizador Global (sem campos de agrupamento)
            $groupKey = '0';
            $groupedResult[$groupKey] = [];
            foreach ($this->data as $item) {
                if (!$hasAggregations) {
                    $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
                } else {
                    $this->groupByCustomAggregations($groupedResult, $groupKey, $compiledAggregations, $item, $avgAccumulators);
                }
            }
        } elseif (count($groupingFields) === 1) {
            // Fast-path para agrupamento por chave única (Single-Key)
            $singleField = $groupingFields[0];
            foreach ($this->data as $item) {
                $val = $item[$singleField] ?? null;
                $groupKey = (string)$val;

                $groupedResult[$groupKey] ??= [$singleField => $val];

                if (!$hasAggregations) {
                    $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
                } else {
                    $this->groupByCustomAggregations($groupedResult, $groupKey, $compiledAggregations, $item, $avgAccumulators);
                }
            }
        } else {
            // Agrupamento composto (Multi-Key)
            foreach ($this->data as $item) {
                $groupHeader = [];
                foreach ($groupingFields as $field) {
                    $val = $item[$field] ?? null;
                    $groupHeader[$field] = $val;
                }
                $groupKey = $this->buildCompositeKey($item, $groupingFields);

                $groupedResult[$groupKey] ??= $groupHeader;

                if (!$hasAggregations) {
                    $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
                } else {
                    $this->groupByCustomAggregations($groupedResult, $groupKey, $compiledAggregations, $item, $avgAccumulators);
                }
            }
        }

        // Calcular médias em O(1) de memória
        if ($hasAvg) {
            $this->resolveGroupAverages($groupedResult, $avgAccumulators);
        }

        $this->data = array_values($groupedResult);
        return $this;
    }

    /**
     * Seleciona os elementos do array de dados com base nas chaves fornecidas.
     * Use `strict: true` para garantir que os dados existam. Uma exceção será lançada
     * caso alguma das chaves solicitadas não exista em pelo menos um item.
     *
     * Suporta aliases no formato ['campo_origem' => 'alias_destino'] ou formato híbrido
     * misturando chaves numéricas (sem alias) e associativas (com alias).
     *
     * @param array<int|string, string> $selectors Lista de chaves a serem selecionadas ou mapa ['origem' => 'alias'], array vazio retorna todas.
     * @param bool $strict     Define o comportamento estrito da seleção.
     *                         - false (padrão): chaves inexistentes retornam `null`
     *                         - true: lança InvalidArgumentException se alguma chave não existir
     *
     * @return LinqPHP Retorna a instância atual para encadeamento (fluent interface).
     * @throws InvalidArgumentException Quando strict = true e uma ou mais chaves não existem.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
     * ];
     *
     * // Seleção simples:
     * $result = LinqPHP::from($data)->select(['name'])->toArray();
     *
     * // Seleção com aliases:
     * $result = LinqPHP::from($data)->select(['id' => 'codigo', 'name' => 'nome_completo'])->toArray();
     *
     * // Seleção híbrida:
     * $result = LinqPHP::from($data)->select(['id' => 'codigo', 'name'])->toArray();
     * ```
     */
    public function select(array $selectors = [], bool $strict = false): self
    {
        if ($selectors === []) {
            return $this;
        }

        // Caminho ultra-rápido quando não há aliases (chaves indexadas numericamente)
        if (array_is_list($selectors)) {
            $resultItems = [];

            // Fast-path para projeção de coluna única
            if (count($selectors) === 1) {
                $col = $selectors[0];
                if ($strict) {
                    foreach ($this->data as $item) {
                        if (!array_key_exists($col, $item)) {
                            throw new InvalidArgumentException(
                                'Select strict mode error. Missing keys: ' . $col
                            );
                        }
                        $resultItems[] = [$col => $item[$col]];
                    }
                } else {
                    foreach ($this->data as $item) {
                        $resultItems[] = [$col => $item[$col] ?? null];
                    }
                }
            } elseif ($strict) {
                $selectorKeys = array_flip($selectors);
                foreach ($this->data as $item) {
                    $missingKeys = array_diff_key($selectorKeys, $item);
                    if ($missingKeys !== []) {
                        throw new InvalidArgumentException(
                            'Select strict mode error. Missing keys: ' . implode(', ', array_keys($missingKeys))
                        );
                    }

                    $row = [];
                    foreach ($selectors as $col) {
                        $row[$col] = $item[$col];
                    }
                    $resultItems[] = $row;
                }
            } else {
                foreach ($this->data as $item) {
                    $row = [];
                    foreach ($selectors as $col) {
                        $row[$col] = $item[$col] ?? null;
                    }
                    $resultItems[] = $row;
                }
            }

            $this->data = $resultItems;
            return $this;
        }

        // Caminho com aliases: normaliza ['id_venda' => 'Id', 'nome_produto']
        $mapping = [];
        foreach ($selectors as $origKey => $aliasOrField) {
            if (is_int($origKey)) {
                $mapping[$aliasOrField] = $aliasOrField;
            } else {
                $mapping[$origKey] = $aliasOrField;
            }
        }

        $resultItems = [];

        if ($strict) {
            $origKeysFlip = array_flip(array_keys($mapping));
            foreach ($this->data as $item) {
                $missingKeys = array_diff_key($origKeysFlip, $item);
                if ($missingKeys !== []) {
                    throw new InvalidArgumentException(
                        'Select strict mode error. Missing keys: ' . implode(', ', array_keys($missingKeys))
                    );
                }

                $selectedItem = [];
                foreach ($mapping as $origField => $aliasField) {
                    $selectedItem[$aliasField] = $item[$origField];
                }
                $resultItems[] = $selectedItem;
            }
        } else {
            foreach ($this->data as $item) {
                $selectedItem = [];
                foreach ($mapping as $origField => $aliasField) {
                    $selectedItem[$aliasField] = $item[$origField] ?? null;
                }
                $resultItems[] = $selectedItem;
            }
        }

        $this->data = $resultItems;
        return $this;
    }

    /**
     * Remove elementos duplicados da coleção, com suporte a projeção de chave por coluna ou closure.
     *
     * Modos de operação:
     * - `null` (padrão): Deduplicação integral do registro via `serialize()` — compatível com arrays aninhados.
     * - `string $keySelector`: Deduplicação por coluna única via lookup direto `O(1)`, eliminando `serialize()` e reduzindo memória em até 80%.
     * - `callable $keySelector`: Deduplicação por chave projetada via closure `fn($item) => $chave` — útil para chaves calculadas.
     *
     * @param string|callable|null $keySelector Campo escalar (string), closure projetora ou null para deduplicação integral.
     * @return self A própria instância fluente com os dados deduplicados.
     *
     * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'cpf' => '111', 'name' => 'Ana'],
     *     ['id' => 2, 'cpf' => '222', 'name' => 'Bob'],
     *     ['id' => 3, 'cpf' => '111', 'name' => 'Ana Clone'],
     * ];
     *
     * // Deduplicação por coluna (O(1), sem serialize)
     * $result = LinqPHP::from($data)->distinct('cpf')->toArray(); // retorna id=1 e id=2
     *
     * // Deduplicação por closure
     * $result = LinqPHP::from($data)->distinct(fn($item) => strtolower($item['name']))->toArray();
     *
     * // Deduplicação integral (comportamento legado)
     * $result = LinqPHP::from($data)->distinct()->toArray();
     * ```
     */
    public function distinct(string|callable|null $keySelector = null): self
    {
        $seenItems = [];
        $resultItems = [];

        if (is_string($keySelector)) {
            // Fast-path: deduplicação por coluna escalar — O(1) via lookup direto, sem serialize()
            foreach ($this->data as $item) {
                $key = (string)($item[$keySelector] ?? '');
                if (!isset($seenItems[$key])) {
                    $seenItems[$key] = true;
                    $resultItems[] = $item;
                }
            }
        } elseif (is_callable($keySelector)) {
            // Deduplicação por closure projetora de chave
            foreach ($this->data as $item) {
                $key = (string)$keySelector($item);
                if (!isset($seenItems[$key])) {
                    $seenItems[$key] = true;
                    $resultItems[] = $item;
                }
            }
        } else {
            // Fallback integral: deduplicação completa do registro via serialize()
            foreach ($this->data as $item) {
                $itemHash = serialize($item);
                if (!isset($seenItems[$itemHash])) {
                    $seenItems[$itemHash] = true;
                    $resultItems[] = $item;
                }
            }
        }

        $this->data = $resultItems;
        return $this;
    }

    /**
     * Obtém um número especificado de itens do array de dados.
     *
     * @param int $maxItens O número máximo de itens a serem obtidos.
     * @return LinqPHP A instância atual da classe.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Jane'],
     *     ['id' => 3, 'name' => 'John'],
     * ];
     *
     * $result = LinqPHP::from($data)->take(2)->toArray();
     * // Resultado: [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']]
     * ```
     */
    public function take(int $maxItens): self
    {
        if ($maxItens < 0) {
            $maxItens = 0;
        }

        $this->data = array_splice($this->data, 0, $maxItens);
        return $this;
    }

    /**
     * Ignora os primeiros itens da coleção, mantendo a interface fluente.
     *
     * @param int $count Quantidade de itens a ignorar.
     * @return self A própria instância com os itens restantes.
     * @throws InvalidArgumentException Quando $count for negativo.
     *
     * @example LinqPHP::from($data)->skip(10)->take(5)->toArray();
     */
    public function skip(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('O número de itens a ignorar não pode ser negativo.');
        }

        $this->data = array_slice($this->data, $count);
        return $this;
    }

    /**
     * Ordena um array de dados por uma chave especificada em ordem ascendente ou descendente.
     *
     * @param string $key A chave pela qual os dados devem ser ordenados.
     * @param bool|string $descending Se verdadeiro ou 'desc', ordena em ordem descendente. Padrão é false ('asc').
     * @return LinqPHP O objeto atual com os dados ordenados.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
     *     ['id' => 3, 'name' => 'John', 'age' => 35],
     * ];
     *
     * // Ordenação descendente com booleano:
     * $result = LinqPHP::from($data)->orderByKey('id', true)->toArray();
     *
     * // Ordenação com string (retrocompatibilidade):
     * $result = LinqPHP::from($data)->orderByKey('id', 'desc')->toArray();
     *
     * // Ordenação por closure de extração / campo calculado:
     * $result = LinqPHP::from($data)->orderByKey(fn($x) => $x['price'] * $x['qty'], true)->toArray();
     *
     * // Ordenação por closure comparadora (estilo usort):
     * $result = LinqPHP::from($data)->orderByKey(fn($a, $b) => strcmp($a['name'], $b['name']))->toArray();
     * ```
     */
    public function orderByKey(callable|string $key, bool|string $descending = false): self
    {
        if ($this->data === []) {
            return $this;
        }

        $isDesc = is_bool($descending) ? $descending : strtolower((string)$descending) === 'desc';

        if ($key instanceof Closure || (is_callable($key) && !is_string($key))) {
            $this->sortWithCallable($key, $isDesc);
            return $this;
        }

        $field = (string)$key;
        $data = array_column($this->data, $field);

        if ($data === []) {
            if (is_callable($key)) {
                $this->sortWithCallable($key, $isDesc);
                return $this;
            }

            throw new InvalidArgumentException('This field does not exist for sorting.');
        }

        if (!$isDesc) {
            array_multisort($data, $this->data);
        } else {
            array_multisort($data, SORT_DESC, $this->data);
        }
        return $this;
    }

    /**
     * Ordena o array de dados de acordo com os campos, direções e funções fornecidos.
     *
     * @param array<int|string, mixed>|callable $fields Os campos ou função de ordenação. Pode ser:
     *                                                  - Uma closure/função única (extração ou comparador)
     *                                                  - Uma lista de campos ['campo1', 'campo2'] (padrão ASC)
     *                                                  - Um array associativo ['campo1' => 'asc', 'campo2' => 'desc']
     *                                                  - Um array com booleanos ['campo1' => false, 'campo2' => true]
     *                                                  - Um array contendo closures: `[fn($x) => $x['total']]`, `[[fn($x) => $x['total'], 'desc']]`
     * @return LinqPHP Retorna a própria instância com os dados ordenados.
     *
     * * Exemplo de uso:
     * ```php
     * // Ordenação com strings:
     * $result = LinqPHP::from($data)->orderBy(['name' => 'asc', 'age' => 'desc'])->toArray();
     *
     * // Ordenação com booleanos (false = ASC, true = DESC):
     * $result = LinqPHP::from($data)->orderBy(['name' => false, 'age' => true])->toArray();
     *
     * // Lista simples (todos ASC por padrão):
     * $result = LinqPHP::from($data)->orderBy(['name', 'age'])->toArray();
     *
     * // Ordenação com closure única:
     * $result = LinqPHP::from($data)->orderBy(fn($x) => $x['price'] * $x['qty'])->toArray();
     *
     * // Misto: campo e closure calculada com direção:
     * $result = LinqPHP::from($data)->orderBy([
     *     'category' => false,
     *     [fn($x) => $x['price'] * $x['qty'], true],
     * ])->toArray();
     * ```
     */
    public function orderBy(array|callable $fields): self
    {
        if ($this->data === []) {
            return $this;
        }

        if ($fields instanceof Closure || (is_callable($fields) && !is_string($fields))) {
            return $this->orderByKey($fields);
        }

        if (!is_array($fields)) {
            if (is_callable($fields)) {
                return $this->orderByKey($fields);
            }
            throw new InvalidArgumentException('orderBy expects an array of fields or a callable.');
        }

        if (count($fields) === 1) {
            $firstKey = array_key_first($fields);
            $firstValue = $fields[$firstKey];
            $candCallable = null;
            $candDir = false;

            if ($firstValue instanceof Closure || (is_callable($firstValue) && !is_string($firstValue))) {
                $candCallable = $firstValue;
            } elseif (is_array($firstValue) && isset($firstValue[0]) && ($firstValue[0] instanceof Closure || (is_callable($firstValue[0]) && !is_string($firstValue[0])))) {
                $candCallable = $firstValue[0];
                $candDir = $firstValue[1] ?? false;
            }

            if ($candCallable !== null && $this->getCallableParamCount($candCallable) === 2) {
                return $this->orderByKey($candCallable, $candDir);
            }
        }

        $sortOrders = [];
        foreach ($fields as $key => $value) {
            $callable = null;
            $sort = SORT_ASC;
            $field = null;

            if ($value instanceof Closure || (is_callable($value) && !is_string($value))) {
                $callable = $value;
                $sort = SORT_ASC;
            } elseif (is_array($value) && isset($value[0]) && ($value[0] instanceof Closure || (is_callable($value[0]) && !is_string($value[0])))) {
                $callable = $value[0];
                $direction = $value[1] ?? false;
                if (is_bool($direction)) {
                    $sort = $direction ? SORT_DESC : SORT_ASC;
                } else {
                    $sort = strtolower((string)$direction) === 'desc' ? SORT_DESC : SORT_ASC;
                }
            } elseif (is_numeric($key)) {
                $field = (string)$value;
                $sort = SORT_ASC;
            } else {
                $field = (string)$key;
                if (is_bool($value)) {
                    $sort = $value ? SORT_DESC : SORT_ASC;
                } else {
                    $sort = strtolower((string)$value) === 'desc' ? SORT_DESC : SORT_ASC;
                }
            }

            if ($callable !== null) {
                $column = $this->extractOrderByColumn($callable);
            } else {
                $column = array_column($this->data, (string)$field);
                if ($column === []) {
                    $column = $this->extractOrderByColumn($field);
                }
            }

            $sortOrders[] = $column;
            $sortOrders[] = $sort;
        }

        $sortOrders[] = &$this->data;
        array_multisort(...$sortOrders);
        return $this;
    }

    /**
     * Summary of toArray
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Converte o objeto atual em um objeto stdClass.
     *
     * @return stdClass {elapsedTime: float, memoryUsed: string, count: int, data: array} O objeto convertido.
     *
     * * Exemplo de saída:
     * ```php
     * $result = LinqPHP::from($data)->toObject();
     * echo $result->elapsedTime; // Output: 0.0160
     * echo $result->memoryUsed; // Output: 0.01MB
     * echo $result->count; // Output: 10_000
     * echo $result->data; // Output: array<int, array<string, mixed>>
     * ```
     */
    public function toObject(): stdClass
    {
        $statistic = $this->statisticUsage();

        $result = new stdClass();
        $result->elapsedTime = $statistic['elapsedTime'];
        $result->memoryUsed = $statistic['memoryUsed'];
        $result->count = count($this->data);
        $result->data = $this->data;

        /** @deprecated use $result->data instead */
        $result->rows = $this->data;

        return $result;
    }

    /**
     * Retorna o primeiro elemento da coleção, ou um valor padrão caso a coleção esteja vazia
     * ou nenhum elemento satisfaça o predicado fornecido.
     *
     * @param (callable(array<string, mixed>): bool)|null $predicate Função opcional para testar cada elemento.
     * @param mixed $default Valor retornado caso nenhum elemento corresponda. Padrão é null.
     * @return mixed O primeiro item correspondente ou o valor padrão.
     *
     * * Exemplo de uso:
     * ```php
     * // Retorna o primeiro item ou null se vazio:
     * $primeiro = LinqPHP::from($users)->firstOrDefault();
     *
     * // Retorna o primeiro item que satisfaz o predicado:
     * $user = LinqPHP::from($users)->firstOrDefault(fn($u) => $u['id'] === 10);
     *
     * // Com valor padrão customizado quando não encontrar:
     * $user = LinqPHP::from($users)->firstOrDefault(fn($u) => $u['id'] === 999, default: ['id' => 0]);
     * ```
     */
    public function firstOrDefault(?callable $predicate = null, mixed $default = null): mixed
    {
        if ($this->data === []) {
            return $default;
        }

        if ($predicate === null) {
            $firstKey = array_key_first($this->data);
            return $firstKey !== null ? $this->data[$firstKey] : $default;
        }

        foreach ($this->data as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Retorna o número de elementos na coleção, opcionalmente filtrados por um predicado.
     *
     * @param (callable(array<string, mixed>): bool)|null $predicate Função opcional para contar apenas itens correspondentes.
     * @return int O número de itens.
     *
     * * Exemplo de uso:
     * ```php
     * $total = LinqPHP::from($vendas)->count();
     * $concluidas = LinqPHP::from($vendas)->count(fn($v) => $v['status'] === 'concluido');
     * ```
     */
    public function count(?callable $predicate = null): int
    {
        if ($predicate === null) {
            return count($this->data);
        }

        $c = 0;
        foreach ($this->data as $item) {
            if ($predicate($item)) {
                $c++;
            }
        }

        return $c;
    }

    /**
     * Calcula a soma numérica de uma coluna ou valor projetado por uma função.
     *
     * @param string|(callable(array<string, mixed>): (int|float)) $field Nome da coluna ou closure que retorna o valor numérico.
     * @return int|float O total somado (retorna 0 para coleção vazia).
     *
     * * Exemplo de uso:
     * ```php
     * $totalQtd = LinqPHP::from($itens)->sum('qtd');
     * $totalFaturado = LinqPHP::from($itens)->sum(fn($i) => $i['qtd'] * $i['preco']);
     * ```
     */
    public function sum(string|callable $field): int|float
    {
        if ($this->data === []) {
            return 0;
        }

        $total = 0;
        $isCallable = is_callable($field);

        foreach ($this->data as $item) {
            $val = $isCallable ? $field($item) : ($item[$field] ?? 0);
            $total += is_numeric($val) ? $val : 0;
        }

        return $total;
    }

    /**
     * Calcula a média aritmética de uma coluna ou valor projetado por uma função.
     *
     * @param string|(callable(array<string, mixed>): (int|float)) $field Nome da coluna ou closure que retorna o valor numérico.
     * @return float|null A média aritmética ou null caso a coleção esteja vazia.
     *
     * * Exemplo de uso:
     * ```php
     * $ticketMedio = LinqPHP::from($vendas)->avg('valor');
     * ```
     */
    public function avg(string|callable $field): ?float
    {
        $count = count($this->data);
        if ($count === 0) {
            return null;
        }

        return (float)($this->sum($field) / $count);
    }

    /**
     * Retorna o menor valor de uma coluna ou calculado por uma função.
     *
     * @param string|(callable(array<string, mixed>): mixed) $field Nome da coluna ou closure de projeção.
     * @return mixed O menor valor encontrado, ou null caso a coleção esteja vazia.
     *
     * * Exemplo de uso:
     * ```php
     * $menorPreco = LinqPHP::from($produtos)->min('preco');
     * ```
     */
    public function min(string|callable $field): mixed
    {
        if ($this->data === []) {
            return null;
        }

        $isCallable = is_callable($field);
        $min = null;
        $first = true;

        foreach ($this->data as $item) {
            $val = $isCallable ? $field($item) : ($item[$field] ?? null);
            if ($first) {
                $min = $val;
                $first = false;
            } elseif ($val !== null && ($min === null || $val < $min)) {
                $min = $val;
            }
        }

        return $min;
    }

    /**
     * Retorna o maior valor de uma coluna ou calculado por uma função.
     *
     * @param string|(callable(array<string, mixed>): mixed) $field Nome da coluna ou closure de projeção.
     * @return mixed O maior valor encontrado, ou null caso a coleção esteja vazia.
     *
     * * Exemplo de uso:
     * ```php
     * $maiorPreco = LinqPHP::from($produtos)->max('preco');
     * ```
     */
    public function max(string|callable $field): mixed
    {
        if ($this->data === []) {
            return null;
        }

        $isCallable = is_callable($field);
        $max = null;
        $first = true;

        foreach ($this->data as $item) {
            $val = $isCallable ? $field($item) : ($item[$field] ?? null);
            if ($first) {
                $max = $val;
                $first = false;
            } elseif ($val !== null && ($max === null || $val > $max)) {
                $max = $val;
            }
        }

        return $max;
    }



    protected function setData(array $data, string $context = 'setData'): void
    {
        $this->data = $this->assertJsonCollection($data, $context);
    }

    protected function assertJsonCollection(mixed $data, string $context = ''): array
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException($context . ' deve ser um array');
        }

        if (!array_is_list($data)) {
            throw new InvalidArgumentException($context . ' deve ser um array indexado (lista)');
        }

        return $data;
    }

    private function normalizeCollection(array $data): array
    {
        // Caso venha um único objeto, converte em lista
        if ($this->isAssoc($data)) {
            return [$data];
        }
        return $data;
    }

    private function isAssoc(array $array): bool
    {
        // return array_keys($array) !== range(0, count($array) - 1);
        return !array_is_list($array);
    }

    private function buildCompositeKey(array $item, array $fields): string
    {
        if (count($fields) === 1) {
            return (string)($item[$fields[0]] ?? '');
        }

        $keyParts = [];
        foreach ($fields as $field) {
            $keyParts[] = (string)($item[$field] ?? null);
        }
        return implode("\0", $keyParts);
    }

    private function nullFillRightOnly(array $leftItem, array $rightSample): array
    {
        if ($rightSample === []) {
            return [];
        }
        $rightKeys = array_keys($rightSample[0]);

        // Remove chaves que já existem no LEFT
        $keysToNull = array_diff($rightKeys, array_keys($leftItem));
        return array_fill_keys($keysToNull, null);
    }

    private function normalizeWhereConditions(mixed $conditions, bool $caseSensitive = false): array
    {
        if (is_callable($conditions) || !is_array($conditions)) {
            $conditions = [$conditions];
        } else {
            $first = reset($conditions);
            if (!is_array($first) && !is_callable($first)) {
                $conditions = [$conditions];
            }
        }

        $normalizedConditions = [];
        foreach ($conditions as $cond) {
            if (is_array($cond) && count($cond) === 3) {
                $val = $cond[2];
                $operator = strtolower((string)$cond[1]);

                if (!$caseSensitive) {
                    if (is_string($val)) {
                        $val = $this->toLower($val);
                    } elseif (is_array($val)) {
                        $val = array_map(fn($v) => is_string($v) ? $this->toLower($v) : $v, $val);
                    }
                }

                if (($operator === 'in' || $operator === 'not in') && is_array($val)) {
                    $canFlip = true;
                    foreach ($val as $v) {
                        if (!is_string($v) && !is_int($v)) {
                            $canFlip = false;
                            break;
                        }
                    }

                    if ($canFlip) {
                        $val = [
                            'raw' => $val,
                            'map' => array_flip($val),
                        ];
                    }
                }

                $normalizedConditions[] = [
                    $cond[0],
                    $operator,
                    $val,
                ];
            } else {
                $normalizedConditions[] = $cond;
            }
        }

        return $normalizedConditions;
    }

    private function matchesConditions(array $item, array $normalizedConditions, bool $caseSensitive = false): bool
    {
        foreach ($normalizedConditions as $condition) {
            if (is_callable($condition)) {
                if (!$condition($item)) {
                    return false;
                }
                continue;
            }

            if (is_array($condition)) {
                $key = $condition[0];
                $operator = $condition[1];
                $value = $condition[2];

                $itemValue = $item[$key] ?? null;
                if (!$caseSensitive && is_string($itemValue)) {
                    $itemValue = $this->toLower($itemValue);
                }

                if (!$this->evaluateOperatorMatch($itemValue, $operator, $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function toLower(string $str): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($str, 'UTF-8') : strtolower($str);
    }

    private function evaluateOperatorMatch(mixed $itemValue, string $operator, mixed $value): bool
    {
        return match ($operator) {
            '>' => $itemValue > $value,
            '>=' => $itemValue >= $value,
            '<' => $itemValue < $value,
            '<=' => $itemValue <= $value,
            '=', '==' => $itemValue == $value,
            '===' => $itemValue === $value,
            '!=', '<>' => $itemValue != $value,
            '!==' => $itemValue !== $value,
            'in' => $this->evaluateIn($itemValue, $value),
            'not in' => !$this->evaluateIn($itemValue, $value),
            'between' => is_array($value) && count($value) === 2
                && $itemValue !== null
                && $itemValue >= $value[0]
                && $itemValue <= $value[1],
            'not between' => is_array($value) && count($value) === 2
                && ($itemValue === null || $itemValue < $value[0] || $itemValue > $value[1]),
            'startswith' => is_string($itemValue) && is_string($value) && str_starts_with($itemValue, $value),
            'endswith' => is_string($itemValue) && is_string($value) && str_ends_with($itemValue, $value),
            'contains' => is_string($itemValue) && is_string($value) && str_contains($itemValue, $value),
            default => throw new InvalidArgumentException("Unsupported operator: {$operator}"),
        };
    }

    private function evaluateIn(mixed $itemValue, mixed $value): bool
    {
        if (is_array($value) && isset($value['map'], $value['raw'])) {
            if (!is_int($itemValue) && !is_string($itemValue)) {
                return false;
            }

            if (isset($value['map'][$itemValue])) {
                if ($value['raw'][$value['map'][$itemValue]] === $itemValue) {
                    return true;
                }

                return in_array($itemValue, $value['raw'], true);
            }

            return false;
        }

        return is_array($value) && in_array($itemValue, $value, true);
    }

    private function groupByDefaultAggregations(
        array &$groupedResult,
        string $groupKey,
        array $groupingFields,
        array $item
    ): void {
        foreach ($item as $key => $value) {
            if (!in_array($key, $groupingFields, true)) {
                if (is_numeric($value)) {
                    // Aplica a soma para as chaves que podem ser somadas
                    $groupedResult[$groupKey][$key] = isset($groupedResult[$groupKey][$key])
                      ? $groupedResult[$groupKey][$key] + (float)$value
                      : (float)$value;
                } else {
                    // Aplica o maior valor para chaves que não podem ser somadas
                    $groupedResult[$groupKey][$key] = isset($groupedResult[$groupKey][$key])
                      ? max($groupedResult[$groupKey][$key], $value)
                      : $value;
                }
            }
        }
    }

    /**
     * @param array<string, array<string, mixed>> $groupedResult
     * @param array<int, array{op: string, field: string, fieldName: string}> $compiledAggregations
     * @param array<string, mixed> $item
     * @param array<string, array<string, array{0: float, 1: int}>> $avgAccumulators
     */
    private function groupByCustomAggregations(
        array &$groupedResult,
        string $groupKey,
        array $compiledAggregations,
        array $item,
        array &$avgAccumulators
    ): void {
        foreach ($compiledAggregations as $agg) {
            $field = $agg['field'];
            $fieldName = $agg['fieldName'];
            $val = $item[$field] ?? null;

            switch ($agg['op']) {
                case 'sum':
                    $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                      ? $groupedResult[$groupKey][$fieldName] + (float)$val
                      : (float)$val;
                    break;
                case 'max':
                    $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                      ? max($groupedResult[$groupKey][$fieldName], $val)
                      : $val;
                    break;
                case 'min':
                    $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                      ? min($groupedResult[$groupKey][$fieldName], $val ?? $groupedResult[$groupKey][$fieldName])
                      : $val;
                    break;
                case 'count':
                    $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                      ? $groupedResult[$groupKey][$fieldName] + 1
                      : 1;
                    break;
                case 'avg':
                    if (!isset($avgAccumulators[$groupKey][$fieldName])) {
                        $avgAccumulators[$groupKey][$fieldName] = [(float)$val, 1];
                    } else {
                        $avgAccumulators[$groupKey][$fieldName][0] += (float)$val;
                        $avgAccumulators[$groupKey][$fieldName][1]++;
                    }
                    break;
            }
        }
    }

    /**
     * @param array<string, array<string, mixed>> $groupedResult
     * @param array<string, array<string, array{0: float, 1: int}>> $avgAccumulators
     */
    private function resolveGroupAverages(array &$groupedResult, array $avgAccumulators): void
    {
        foreach ($avgAccumulators as $groupKey => $fields) {
            foreach ($fields as $fieldName => [$sum, $count]) {
                $groupedResult[$groupKey][$fieldName] = $count > 0 ? $sum / $count : null;
            }
        }
    }

    private function getCallableParamCount(callable $callable): int
    {
        $ref = new ReflectionFunction(Closure::fromCallable($callable));
        return $ref->getNumberOfParameters();
    }

    private function sortWithCallable(callable $callable, bool $isDesc): void
    {
        if ($this->getCallableParamCount($callable) === 2) {
            if ($isDesc) {
                usort($this->data, static fn($a, $b) => $callable($b, $a));
            } else {
                usort($this->data, $callable);
            }
        } else {
            $values = array_map($callable, $this->data);
            if (!$isDesc) {
                array_multisort($values, $this->data);
            } else {
                array_multisort($values, SORT_DESC, $this->data);
            }
        }
    }

    private function extractOrderByColumn(mixed $fieldOrCallable): array
    {
        if (is_callable($fieldOrCallable)) {
            if ($this->getCallableParamCount($fieldOrCallable) === 2) {
                throw new InvalidArgumentException(
                    'Comparator callables with two parameters cannot be combined with multiple fields in orderBy.'
                );
            }
            return array_map($fieldOrCallable, $this->data);
        }

        throw new InvalidArgumentException('This field does not exist for sorting.');
    }

    private function statisticUsage(): array
    {
        $endTime = microtime(true);

        return [
            'elapsedTime' => number_format($endTime - $this->startTime, 6),
            'memoryUsed' => sprintf('%5.2f MB', (memory_get_peak_usage(true) - $this->startMemory) / 1024 / 1024),
        ];
    }
}
