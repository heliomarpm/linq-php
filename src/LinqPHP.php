<?php

namespace HeliomarPM\LinqPHP;

use InvalidArgumentException;

enum JoinType: string
{
  case INNER = 'INNER';
  case LEFT = 'LEFT';
  case RIGHT = 'RIGHT';
  case FULL = 'FULL';
}

class LinqPHP
{
  private array $data = [];
  private float $startTime;
  private float $endTime;
  private int $startMemory;

  public function __construct(array $data)
  {
    // $this->startTime = round(microtime(true) * 1000); //tempo em milisegundos
    $this->startTime = microtime(true); //tempo em micros
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


  /**
   * Concatena dois arrays em um único array preservando todos os elementos e chaves.
   *
   * @param array<int, array<string, mixed>>$unionData O array a ser concatenado com o array atual.
   * @return LinqPHP Retorna a instância atual da classe.
   *
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
    if (empty($this->data)) {
      $this->data = $unionData;
      return $this;
    }

    if (empty($unionData)) {
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

  /**
   * Junta o array atual com outro array com base em campos relacionados.
   *
   * @param array<int, array<string, mixed>>$joinData O array a ser unido.
   * @param JoinType $joinType O tipo de união a ser realizada. Os valores válidos são "INNER", "LEFT", "RIGHT" ou "FULL". O padrão é "INNER".
   * @param array<int, array<string, mixed>>$relatedFields Os campos usados para determinar a relação entre os arrays. O padrão é um array vazio.
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
  public function join(array $joinData, JoinType $joinType, array $relatedFields = []): self
  {
    // if (!in_array($joinType, ['INNER', 'LEFT', 'RIGHT', 'FULL'])) {
    //   throw new InvalidArgumentException('Invalid join type.');
    // }

    if (empty($relatedFields)) {
      throw new InvalidArgumentException('Join requires related fields.');
    }

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
    // Indexa RIGHT
    // ============================
    $rightIndex = [];
    foreach ($joinData as $r) {
      $key = $this->buildJoinKey($r, $rightFields);
      $rightIndex[$key][] = $r;
    }

    $result = [];
    $matchedRightKeys = [];

    // ============================
    // INNER / LEFT / FULL
    // ============================
    foreach ($this->data as $l) {
      $key = $this->buildJoinKey($l, $leftFields);

      if (isset($rightIndex[$key])) {
        foreach ($rightIndex[$key] as $r) {
          $result[] = array_merge($l, $r);
        }
        $matchedRightKeys[$key] = true;
      } elseif (in_array($joinType, [JoinType::LEFT, JoinType::FULL])) {
        $result[] = array_merge($l, $this->nullFillRightOnly($l, $joinData));
      }
    }

    // ============================
    // RIGHT / FULL
    // ============================
    if (in_array($joinType, [JoinType::RIGHT, JoinType::FULL])) {
      foreach ($joinData as $r) {
        $key = $this->buildJoinKey($r, $rightFields);

        if (!isset($matchedRightKeys[$key])) {
          $result[] = array_merge($this->nullFillRightOnly($r, $this->data), $r);
        }
      }
    }

    $this->data = $result;
    $this->endTime = microtime(true);
    return $this;
  }

  private function buildJoinKey(array $item, array $fields): string
  {
    $values = [];
    foreach ($fields as $field) {
      $values[] = $item[$field] ?? null;
    }
    return implode("\0", $values);
  }

  private function nullFillRightOnly(array $leftItem, array $rightSample): array
  {
    if (empty($rightSample)) {
      return [];
    }
    $rightKeys = array_keys($rightSample[0]);

    // Remove chaves que já existem no LEFT
    $keysToNull = array_diff($rightKeys, array_keys($leftItem));
    return array_fill_keys($keysToNull, null);
  }

  /**
   * Aceita arrays de condições simples ou funções (closures) para filtros complexos.
   *
   * @param array<int, array<string, mixed>>|callable $conditions As condições para filtrar o array de dados. Pode ser um array simples, uma função, ou um array de arrays/funções.
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
    // Se a condição é um array simples ou uma função, transforme-a em um array de arrays
    // if (!is_array(reset($conditions)) && !is_callable(reset($conditions))) {
    //     $conditions = [$conditions];
    // }

    if (is_callable($conditions)) {
      $conditions = [$conditions];
    } elseif (!is_array($conditions)) {
      $conditions = [$conditions];
    } else {
      $first = reset($conditions);
      if (!is_array($first) && !is_callable($first)) {
        $conditions = [$conditions];
      }
    }

    // Pré-processa as condições uma única vez
    $normalizedConditions = [];
    foreach ($conditions as $cond) {
      if (is_array($cond) && count($cond) === 3) {
        $normalizedConditions[] = [
          $cond[0],
          strtolower($cond[1]), // Operador em minúsculo
          is_string($cond[2]) ? strtolower($cond[2]) : $cond[2]
        ];
      } else {
        $normalizedConditions[] = $cond;
      }
    }

    $resultadosFiltrados = array_filter($this->data, function ($item) use ($normalizedConditions) {
      foreach ($normalizedConditions as $condition) {
        // Se a condição é uma função, chamamos a função passando o item
        // Se a função retornar false, o item é removido
        if (is_callable($condition)) {
          if (!$condition($item)) {
            return false;
          }
          continue;
        }

        // Se a condição é um array, assumimos que é um critério de filtro comum
        if (is_array($condition)) {
          [$key, $operator, $value] = $condition;

          $itemValue = $item[$key] ?? null;
          if (is_string($itemValue)) {
            $itemValue = strtolower($itemValue);
          }

          $isMatch = match ($operator) {
            '>' => $itemValue > $value,
            '>=' => $itemValue >= $value,
            '<' => $itemValue < $value,
            '<=' => $itemValue <= $value,
            '=' => $itemValue == $value,
            'in' => is_array($value) && in_array($itemValue, $value),
            'startswith' => is_string($itemValue) && is_string($value) && str_starts_with($itemValue, $value),
            'endswith' => is_string($itemValue) && is_string($value) && str_ends_with($itemValue, $value),
            'contains' => is_string($itemValue) && is_string($value) && str_contains($itemValue, $value),
            default => throw new InvalidArgumentException("Unsupported operator: $operator"),
          };

          if (!$isMatch) {
            return false;
          }
        }
      }
      return true;
    });

    $this->data = array_values($resultadosFiltrados);
    $this->endTime = microtime(true);
    return $this;
  }

  /**
   * Agrupa os dados com base nos campos especificados e realiza agregações (sum, count, max, min, avg).
   *
   * @param array<int, array<string, mixed>>$groupingFields Os campos para agrupar os dados.
   * @param array<int, array<string, mixed>>$aggregations As agregações a serem realizadas nos dados.
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
    $groupedResult = [];
    $aggregated = true;

    foreach ($this->data as $item) {
      // Gerar chave única para identificar o grupo
      $groupKey = implode('_', array_intersect_key($item, array_flip($groupingFields)));

      // Inicializar grupo se ainda não existir
      if (!isset($groupedResult[$groupKey])) {
        $groupedResult[$groupKey] = array_intersect_key($item, array_flip($groupingFields));
      }

      if (empty($aggregations)) {
        if ($aggregated) {
          $this->groupByDefaultAggregations($groupedResult, $groupKey, $groupingFields, $item);
        }
      } else {
        $this->groupByCustomAggregations($groupedResult, $groupKey, $aggregations, $item);
      }
    }

    // Calcular médias
    foreach ($aggregations as $operation => $fields) {
      if ($operation === 'avg') {
        foreach ($groupedResult as &$group) {
          foreach ($fields as $field) {
            $avgField = $field;
            if (array_key_exists($field . "_(avg)", $group)) {
              $avgField .= "_(avg)";
            }
            $sum = array_sum($group[$avgField]);
            $count = count($group[$avgField]);
            $group[$avgField] = $count > 0 ? $sum / $count : null;
          }
        }
      }
    }

    $this->data = array_values($groupedResult);
    $this->endTime = microtime(true);
    return $this;
  }

  private function groupByDefaultAggregations(&$groupedResult, $groupKey, $groupingFields, $item)
  {
    $aggregated = false;
    foreach ($item as $key => $value) {
      if (!in_array($key, $groupingFields)) {
        $aggregated = true;
        if (is_numeric($value)) {
          // Aplica a soma para as chaves que podem ser somadas
          $groupedResult[$groupKey][$key] = isset($groupedResult[$groupKey][$key])
            ? $groupedResult[$groupKey][$key] + (float) $value
            : (float) $value;
        } else {
          // Aplica o maior valor para chaves que não podem ser somadas
          $groupedResult[$groupKey][$key] = isset($groupedResult[$groupKey][$key])
            ? max($groupedResult[$groupKey][$key], $value)
            : $value;
        }
      }
    }

    return $aggregated;
  }

  private function groupByCustomAggregations(&$groupedResult, $groupKey, $aggregations, $item)
  {
    // Obtém todos os campos e conta a frequência de cada valor
    $valueCounts = array_count_values(array_merge(...array_values($aggregations)));

    // Filtra e retorna todos os valores que se repetem
    $aggregationsKeyDuplicated = array_keys(array_filter($valueCounts, fn($count) => $count > 1));

    foreach ($aggregations as $operation => $fields) {
      $operation = strtolower($operation);

      foreach ($fields as $field) {
        $fieldName = $field;

        // Se chave vai se repetir então concatena com operador
        if (in_array($field, $aggregationsKeyDuplicated)) {
          $fieldName .= "_($operation)";
        }

        switch ($operation) {
          case 'sum':
            $groupedResult[$groupKey][$fieldName] = isset($groupedResult[$groupKey][$fieldName])
              ? $groupedResult[$groupKey][$fieldName] + (float) $item[$field]
              : (float) $item[$field];
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
            $groupedResult[$groupKey][$fieldName][] = (float) $item[$field];
            break;
        }
      }
    }
  }

  /**
   * Seleciona os elementos do array de dados com base nas chaves fornecidas.
   * Use `strict: true` para garantir que os dados existam. Uma exceção será lançada
   * caso alguma das chaves solicitadas não exista em pelo menos um item.
   *
   * @param array<int, array<string, mixed>>$selectors Lista de chaves a serem selecionadas, array vazio retorna todas.
   * @param bool $strict     Define o comportamento estrito da seleção.
   *                         - false (padrão): chaves inexistentes retornam `null`
   *                         - true: lança InvalidArgumentException se alguma chave não existir
   *
   * @return LinqPHP Retorna a instãncia atual para encadeamento (fluent interface).
   * @throws InvalidArgumentException Quando strict = true e uma ou mais chaves não existem.
   *
   * * Exemplo de uso:
   * ```php
   * $data = [
   *     ['id' => 1, 'name' => 'John', 'age' => 25],
   *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
   *     ['id' => 3, 'name' => 'John', 'age' => 35],
   * ];
   *
   * $result = LinqPHP::from($data)->select(['name'], distinct: true)->toArray();
   * // Resultado:
   * // [ ['name' => 'John'], ['name' => 'Jane'] ]
   *
   * $result = LinqPHP::from($data)->select(['name', 'gender'], distinct: true)->toArray();
   * // Resultado:
   * // [ ['name' => 'John', 'gender' => null], ['name' => 'Jane', 'gender' => null] ]
   *
   * // Modo estrito
   * $result = LinqPHP::from($data)
   *     ->select(['name', 'gender'], strict: true)
   *     ->toArray(); // lança InvalidArgumentException
   * ```
   */
  public function select(array $selectors = [], bool $strict = false): self
  {
    $seenItems = [];
    $resultItems = [];

    $selectorKeys = array_flip($selectors);
    $nullTemplate = array_fill_keys($selectors, null);

    foreach ($this->data as $item) {
      if (empty($selectors)) {
        $selectedItem = $item;
      } else {
        if ($strict) {
          // verifica se todas as chaves solicitadas existem em cada item
          $missingKeys = array_diff_key($selectorKeys, $item);
          if (!empty($missingKeys)) {
            throw new InvalidArgumentException(
              'Select strict mode error. Missing keys: ' . implode(', ', array_keys($missingKeys))
            );
          }
        }

        $selectedItem = array_replace($nullTemplate, array_intersect_key($item, $selectorKeys));
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
   * @param string $order A ordem na qual os dados devem ser ordenados. Padrão é 'asc'.
   *                     Valores válidos são 'asc' para ordem ascendente e 'desc' para ordem descendente.
   * @return LinqPHP O objeto atual.
   *
   * * Exemplo de uso:
   * ```php
   * $data = [
   *     ['id' => 1, 'name' => 'John', 'age' => 25],
   *     ['id' => 2, 'name' => 'Jane', 'age' => 30],
   *     ['id' => 3, 'name' => 'John', 'age' => 35],
   * ];
   *
   * $result = LinqPHP::from($data)->orderByKey('id', 'desc')->toArray();
   * ```
   */
  public function orderByKey(string $key, string $order = 'asc'): self
  {
    $data = array_column($this->data, $key);

    if (empty($data)) {
      throw new InvalidArgumentException("This field does not exist for sorting.");
    }

    if (strtolower($order) === 'asc') {
      array_multisort($data, $this->data);
    } else {
      array_multisort($data, SORT_DESC, $this->data);
    }
    $this->endTime = microtime(true);
    return $this;
  }


  /**
   * Ordena o array de dados de acordo com a ordem fornecida.
   *
   * @param array $fields
   * @return LinqPHP
   */
  public function orderBy(array $fields): self
  {
    $sortOrders = [];
    foreach ($fields as $field => $sortType) {
      $field = is_numeric($field) ? $sortType : $field;
      $column = array_column($this->data, $field);
      $sortOrders[] = $column;
      $sortOrders[] = ($sortType === 'desc') ? SORT_DESC : SORT_ASC;
    }

    $sortOrders[] = &$this->data;
    array_multisort(...$sortOrders); // Substituído call_user_func_array pelo spread operator (...)

    $this->endTime = microtime(true);
    return $this;
  }

  private function statisticUsage(): array
  {
    return [
      'elapsedTime' => number_format($this->endTime - $this->startTime, 6),
      'memoryUsed' => sprintf("%5.2f MB", (memory_get_peak_usage(true) - $this->startMemory) / 1024 / 1024),
    ];
  }

  /**
   * Summary of toArray
   * @return array
   */
  public function toArray(): array
  {
    return $this->data;
  }

  /**
   * Converte o objeto atual em um objeto stdClass.
   *
   * @return \stdClass {elapsedTime: float, count: int, rows: array} O objeto convertido.
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
  public function toObject(): \stdClass
  {
    $statistic = $this->statisticUsage();

    $result = new \stdClass();
    $result->elapsedTime = $statistic['elapsedTime'];
    $result->memoryUsed = $statistic['memoryUsed'];
    $result->count = count($this->data);
    $result->data = $this->data;

    return $result;
  }
}
