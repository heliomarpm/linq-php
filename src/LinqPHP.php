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
    private float $endTime;
    private readonly int $startMemory;

    public function __construct(array $data)
    {
        $this->startTime = microtime(true); // tempo em micros
        $this->endTime = $this->startTime;
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
        // Caso venha um único objeto, converte em lista
        if ($this->data === []) {
            $this->data = $unionData;
            return $this;
        }

        if ($unionData === []) {
            return $this;
        }

        $unionData = $this->normalizeCollection($unionData);
        // Coleta todas as chaves existentes em ambos os conjuntos
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

        $allKeys = array_keys($allKeys);

        // Normaliza cada linha para conter todas as chaves
        $normalize = static fn(array $row) => array_replace(array_fill_keys($allKeys, null), $row);

        $this->data = array_merge(
            array_map($normalize, $this->data),
            array_map($normalize, $unionData)
        );

        $this->endTime = microtime(true);
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
            $key = $singleRight !== null ? (string)($r[$singleRight] ?? '') : $this->buildJoinKey($r, $rightFields);
            $rightIndex[$key][] = $r;
        }

        $result = [];
        $matchedRightKeys = [];
        $singleLeft = count($leftFields) === 1 ? $leftFields[0] : null;

        // ============================
        // INNER / LEFT / FULL
        // ============================
        foreach ($this->data as $l) {
            $key = $singleLeft !== null ? (string)($l[$singleLeft] ?? '') : $this->buildJoinKey($l, $leftFields);

            if (isset($rightIndex[$key])) {
                foreach ($rightIndex[$key] as $r) {
                    $result[] = array_merge($l, $r);
                }
                $matchedRightKeys[$key] = true;
            } elseif (in_array($joinType, [JoinType::LEFT, JoinType::FULL], true)) {
                $result[] = array_merge($l, $this->nullFillRightOnly($l, $joinData));
            }
        }

        // ============================
        // RIGHT / FULL
        // ============================
        if (in_array($joinType, [JoinType::RIGHT, JoinType::FULL], true)) {
            foreach ($joinData as $r) {
                $key = $singleRight !== null ? (string)($r[$singleRight] ?? '') : $this->buildJoinKey($r, $rightFields);

                if (!isset($matchedRightKeys[$key])) {
                    $result[] = array_merge($this->nullFillRightOnly($r, $this->data), $r);
                }
            }
        }

        $this->data = $result;
        $this->endTime = microtime(true);
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
     * Aceita arrays de condições simples ou funções (closures) para filtros complexos.
     *
     * @param mixed $conditions As condições para filtrar o array de dados. Pode ser um array simples, uma função, ou um array de arrays/funções.
     * @return LinqPHP O array de dados filtrado.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
     *     ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
     * ];
     *
     * // Usando múltiplas condições incluindo funções
     * $conditions = [
     *     ['age', '>', 18],
     *     ['city', '=', 'New York'],
     *     fn($p) => $p['id'] % 2 == 0,
     *     function ($item) {
     *         return $item['status'] === 'active' || $item['status'] === 'pending'; \
     *     },
     * ];
     *
     * $result = LinqPHP::from($data)->where($conditions)->toArray();
     *
     * // Múltiplos filtros e operadores (startswith, endswith, contains, in)
     * $result = LinqPHP::from($data)
     *     ->where([
     * 	      ['age', '>=', 25],
     *        ['status', 'in', ['active', 'pending']],
     *        ['name', 'startswith', 'J']
     *     ])
     *     ->toArray();
     *
     * // Usando Closures (funções anônimas)
     * $result = LinqPHP::from($data)
     *     ->where(fn($item) => $item['id'] % 2 !== 0)
     *     ->toArray();
     *
     * // Usando um array simples
     * $condition = ['age', '>', 30];
     * $result = LinqPHP::from($data)->where($conditions)->toArray();
     *
     * // Usando uma função
     * $condition = fn($item) => $item['id'] % 2 == 0;
     * $result = LinqPHP::from($data)->where($conditions)->toArray();
     * ```
     */
    public function where(mixed $conditions): self
    {
        // Fast-path 1: Se for uma closure/função única, executa direto sem wrapper
        if (is_callable($conditions) && !is_array($conditions)) {
            $this->data = array_values(array_filter($this->data, $conditions));
            $this->endTime = microtime(true);
            return $this;
        }

        $normalizedConditions = $this->normalizeWhereConditions($conditions);

        // Fast-path 2: Condição única estruturada (ex: ['status', '=', 'concluido'])
        if (count($normalizedConditions) === 1 && is_array($normalizedConditions[0]) && count($normalizedConditions[0]) === 3) {
            $cond = $normalizedConditions[0];
            $key = $cond[0];
            $operator = $cond[1];
            $value = $cond[2];

            $filtered = [];
            foreach ($this->data as $item) {
                $itemValue = $item[$key] ?? null;
                if (is_string($itemValue)) {
                    $itemValue = strtolower($itemValue);
                }
                if ($this->evaluateOperatorMatch($itemValue, $operator, $value)) {
                    $filtered[] = $item;
                }
            }

            $this->data = $filtered;
            $this->endTime = microtime(true);
            return $this;
        }

        $resultadosFiltrados = array_filter(
            $this->data,
            fn(array $item): bool => $this->matchesConditions($item, $normalizedConditions)
        );

        $this->data = array_values($resultadosFiltrados);
        $this->endTime = microtime(true);
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
            $this->endTime = microtime(true);
            return $this;
        }

        $groupedResult = [];
        $hasAggregations = $aggregations !== [];
        $duplicatedFields = [];
        $isGlobal = ($groupingFields === []);

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
        }

        if ($isGlobal) {
            // Fast-path para Totalizador Global (sem campos de agrupamento)
            $groupKey = '0';
            $groupedResult[$groupKey] = [];
            foreach ($this->data as $item) {
                if (!$hasAggregations) {
                    $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
                } else {
                    $this->groupByCustomAggregations($groupedResult, $groupKey, $aggregations, $duplicatedFields, $item);
                }
            }
        } else {
            $groupingFieldsFlip = array_flip($groupingFields);
            foreach ($this->data as $item) {
                // Gerar chave única para identificar o grupo
                $groupKey = implode('_', array_intersect_key($item, $groupingFieldsFlip));

                // Inicializar grupo se ainda não existir
                $groupedResult[$groupKey] ??= array_intersect_key($item, $groupingFieldsFlip);

                if (!$hasAggregations) {
                    $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
                } else {
                    $this->groupByCustomAggregations($groupedResult, $groupKey, $aggregations, $duplicatedFields, $item);
                }
            }
        }

        // Calcular médias
        if ($hasAggregations) {
            $this->resolveGroupAverages($groupedResult, $aggregations);
        }

        $this->data = array_values($groupedResult);
        $this->endTime = microtime(true);
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
            $this->endTime = microtime(true);
            return $this;
        }

        // Caminho ultra-rápido quando não há aliases (chaves indexadas numericamente)
        if (array_is_list($selectors)) {
            $selectorKeys = array_flip($selectors);
            $nullTemplate = array_fill_keys($selectors, null);
            $resultItems = [];

            foreach ($this->data as $item) {
                if ($strict) {
                    $missingKeys = array_diff_key($selectorKeys, $item);
                    if ($missingKeys !== []) {
                        throw new InvalidArgumentException(
                            'Select strict mode error. Missing keys: ' . implode(', ', array_keys($missingKeys))
                        );
                    }
                }

                $resultItems[] = array_replace($nullTemplate, array_intersect_key($item, $selectorKeys));
            }

            $this->data = $resultItems;
            $this->endTime = microtime(true);
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

        $origKeysFlip = array_flip(array_keys($mapping));
        $resultItems = [];

        foreach ($this->data as $item) {
            if ($strict) {
                $missingKeys = array_diff_key($origKeysFlip, $item);
                if ($missingKeys !== []) {
                    throw new InvalidArgumentException(
                        'Select strict mode error. Missing keys: ' . implode(', ', array_keys($missingKeys))
                    );
                }
            }

            $selectedItem = [];
            foreach ($mapping as $origField => $aliasField) {
                $selectedItem[$aliasField] = $item[$origField] ?? null;
            }
            $resultItems[] = $selectedItem;
        }

        $this->data = $resultItems;
        $this->endTime = microtime(true);
        return $this;
    }

    /**
     * Remove os elementos duplicados do array de dados.
     *
     * @return LinqPHP A instância atual da classe após a remoção dos elementos duplicados.
     *
     * * Exemplo de uso:
     * ```php
     * $data = [
     *     ['id' => 1, 'name' => 'John', 'age' => 25],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
     *     ['id' => 3, 'name' => 'John', 'age' => 35],
     * ];
     *
     * $result = LinqPHP::from($data)->distinct()->toArray();
     * ```
     */
    public function distinct(): self
    {
        $seenItems = [];
        $resultItems = [];

        foreach ($this->data as $item) {
            $itemHash = serialize($item);

            if (isset($seenItems[$itemHash])) {
                continue;
            }

            $seenItems[$itemHash] = true;
            $resultItems[] = $item;
        }

        $this->data = $resultItems;
        $this->endTime = microtime(true);
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
        $this->endTime = microtime(true);
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
            $this->endTime = microtime(true);
            return $this;
        }

        $isDesc = is_bool($descending) ? $descending : strtolower((string)$descending) === 'desc';

        if ($key instanceof Closure || (is_callable($key) && !is_string($key))) {
            $ref = new ReflectionFunction(Closure::fromCallable($key));
            if ($ref->getNumberOfParameters() === 2) {
                if ($isDesc) {
                    usort($this->data, static fn($a, $b) => $key($b, $a));
                } else {
                    usort($this->data, $key);
                }
            } else {
                $values = array_map($key, $this->data);
                if (!$isDesc) {
                    array_multisort($values, $this->data);
                } else {
                    array_multisort($values, SORT_DESC, $this->data);
                }
            }
            $this->endTime = microtime(true);
            return $this;
        }

        $field = (string)$key;
        $data = array_column($this->data, $field);

        if ($data === []) {
            if (is_callable($key)) {
                $ref = new ReflectionFunction(Closure::fromCallable($key));
                if ($ref->getNumberOfParameters() === 2) {
                    if ($isDesc) {
                        usort($this->data, static fn($a, $b) => $key($b, $a));
                    } else {
                        usort($this->data, $key);
                    }
                } else {
                    $values = array_map($key, $this->data);
                    if (!$isDesc) {
                        array_multisort($values, $this->data);
                    } else {
                        array_multisort($values, SORT_DESC, $this->data);
                    }
                }
                $this->endTime = microtime(true);
                return $this;
            }

            throw new InvalidArgumentException("This field does not exist for sorting.");
        }

        if (!$isDesc) {
            array_multisort($data, $this->data);
        } else {
            array_multisort($data, SORT_DESC, $this->data);
        }
        $this->endTime = microtime(true);
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
            $this->endTime = microtime(true);
            return $this;
        }

        if ($fields instanceof Closure || (is_callable($fields) && !is_string($fields))) {
            return $this->orderByKey($fields);
        }

        if (!is_array($fields)) {
            if (is_callable($fields)) {
                return $this->orderByKey($fields);
            }
            throw new InvalidArgumentException("orderBy expects an array of fields or a callable.");
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

            if ($candCallable !== null) {
                $ref = new ReflectionFunction(Closure::fromCallable($candCallable));
                if ($ref->getNumberOfParameters() === 2) {
                    return $this->orderByKey($candCallable, $candDir);
                }
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
                $ref = new ReflectionFunction(Closure::fromCallable($callable));
                if ($ref->getNumberOfParameters() === 2) {
                    throw new InvalidArgumentException(
                        "Comparator callables with two parameters cannot be combined with multiple fields in orderBy."
                    );
                }
                $column = array_map($callable, $this->data);
            } else {
                $column = array_column($this->data, (string)$field);
                if ($column === []) {
                    if (is_callable($field)) {
                        $ref = new ReflectionFunction(Closure::fromCallable($field));
                        if ($ref->getNumberOfParameters() === 2) {
                            throw new InvalidArgumentException(
                                "Comparator callables with two parameters cannot be combined with multiple fields in orderBy."
                            );
                        }
                        $column = array_map($field, $this->data);
                    } else {
                        throw new InvalidArgumentException("This field does not exist for sorting.");
                    }
                }
            }

            $sortOrders[] = $column;
            $sortOrders[] = $sort;
        }

        $sortOrders[] = &$this->data;
        array_multisort(...$sortOrders);

        $this->endTime = microtime(true);
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
        $this->endTime = microtime(true);

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
        $this->endTime = microtime(true);

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
        $this->endTime = microtime(true);

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
        $this->endTime = microtime(true);

        $count = count($this->data);
        if ($count === 0) {
            return null;
        }

        $total = 0;
        $isCallable = is_callable($field);

        foreach ($this->data as $item) {
            $val = $isCallable ? $field($item) : ($item[$field] ?? 0);
            $total += is_numeric($val) ? $val : 0;
        }

        return (float)($total / $count);
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
        $this->endTime = microtime(true);

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
        $this->endTime = microtime(true);

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

    private function buildJoinKey(array $item, array $fields): string
    {
        if (count($fields) === 1) {
            return (string)($item[$fields[0]] ?? '');
        }

        $values = [];
        foreach ($fields as $field) {
            $values[] = $item[$field] ?? null;
        }
        return implode("\0", $values);
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

    private function normalizeWhereConditions(mixed $conditions): array
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
                $normalizedConditions[] = [
                    $cond[0],
                    strtolower((string)$cond[1]),
                    is_string($cond[2]) ? strtolower($cond[2]) : $cond[2],
                ];
            } else {
                $normalizedConditions[] = $cond;
            }
        }

        return $normalizedConditions;
    }

    private function matchesConditions(array $item, array $normalizedConditions): bool
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
                if (is_string($itemValue)) {
                    $itemValue = strtolower($itemValue);
                }

                if (!$this->evaluateOperatorMatch($itemValue, $operator, $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function evaluateOperatorMatch(mixed $itemValue, string $operator, mixed $value): bool
    {
        return match ($operator) {
            '>' => $itemValue > $value,
            '>=' => $itemValue >= $value,
            '<' => $itemValue < $value,
            '<=' => $itemValue <= $value,
            '=' => $itemValue == $value,
            'in' => is_array($value) && in_array($itemValue, $value),
            'startswith' => is_string($itemValue) && is_string($value) && str_starts_with($itemValue, $value),
            'endswith' => is_string($itemValue) && is_string($value) && str_ends_with($itemValue, $value),
            'contains' => is_string($itemValue) && is_string($value) && str_contains($itemValue, $value),
            default => throw new InvalidArgumentException("Unsupported operator: {$operator}"),
        };
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

    private function groupByCustomAggregations(
        array &$groupedResult,
        string $groupKey,
        array $aggregations,
        array $duplicatedFields,
        array $item
    ): void {
        foreach ($aggregations as $operation => $fields) {
            $operation = strtolower($operation);

            foreach ($fields as $field) {
                $fieldName = in_array($field, $duplicatedFields, true)
                  ? $field . "_({$operation})"
                  : $field;

                switch ($operation) {
                    case 'sum':
                        $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                          ? $groupedResult[$groupKey][$fieldName] + (float)$item[$field]
                          : (float)$item[$field];
                        break;
                    case 'max':
                        $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                          ? max($groupedResult[$groupKey][$fieldName], $item[$field])
                          : $item[$field];
                        break;
                    case 'min':
                        $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                          ? min($groupedResult[$groupKey][$fieldName], $item[$field] ?? $groupedResult[$groupKey][$fieldName])
                          : $item[$field];
                        break;
                    case 'count':
                        $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
                          ? $groupedResult[$groupKey][$fieldName] + 1
                          : 1;
                        break;
                    case 'avg':
                        $groupedResult[$groupKey][$fieldName][] = (float)$item[$field];
                        break;
                }
            }
        }
    }

    private function resolveGroupAverages(array &$groupedResult, array $aggregations): void
    {
        foreach ($aggregations as $operation => $fields) {
            if (strtolower($operation) === 'avg') {
                foreach ($groupedResult as &$group) {
                    foreach ($fields as $field) {
                        $avgField = $field;
                        if (array_key_exists($field . "_(avg)", $group)) {
                            $avgField .= "_(avg)";
                        }
                        if (isset($group[$avgField]) && is_array($group[$avgField])) {
                            $sum = array_sum($group[$avgField]);
                            $count = count($group[$avgField]);
                            $group[$avgField] = $count > 0 ? $sum / $count : null;
                        }
                    }
                }
                unset($group);
            }
        }
    }

    private function statisticUsage(): array
    {
        return [
            'elapsedTime' => number_format($this->endTime - $this->startTime, 6),
            'memoryUsed' => sprintf("%5.2f MB", (memory_get_peak_usage(true) - $this->startMemory) / 1024 / 1024),
        ];
    }
}
