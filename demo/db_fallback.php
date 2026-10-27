<?php

namespace HeliomarPM\DbPHP;

use ArrayIterator;
use HeliomarPM\LinqPHP\LinqPHP;
use IteratorAggregate;
use PDO;
use Traversable;

if (!class_exists(DB::class)) {
    /**
     * Fallback leve e auto-contido do DbPHP para ambientes de demonstração web (ex: Vercel)
     * onde dependências de desenvolvimento (require-dev) não são instaladas.
     */
    class DB
    {
        private static ?PDO $pdo = null;
        private static array $mockTables = [];

        public static function sqlite(?string $path = ':memory:'): ?PDO
        {
            if (!extension_loaded('pdo_sqlite')) {
                return null;
            }

            if (self::$pdo === null) {
                self::$pdo = new PDO('sqlite:' . ($path ?? ':memory:'));
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }

            return self::$pdo;
        }

        public static function execute(string $sql, array $params = []): bool
        {
            $pdo = self::sqlite();
            if ($pdo !== null) {
                $stmt = $pdo->prepare($sql);

                return $stmt->execute($params);
            }

            // Fallback em memória pura caso pdo_sqlite não esteja disponível
            if (preg_match('/INSERT\s+INTO\s+vendas_sql/i', $sql)) {
                self::$mockTables['vendas_sql'][] = [
                    'id_venda'   => $params[0] ?? 0,
                    'produto_id' => $params[1] ?? 0,
                    'cliente_id' => $params[2] ?? 0,
                    'qtd'        => $params[3] ?? 0,
                    'canal'      => $params[4] ?? '',
                    'status'     => $params[5] ?? '',
                ];
            }

            return true;
        }

        public static function query(string $sql, array $params = []): DbQueryResult
        {
            $pdo = self::sqlite();
            if ($pdo !== null) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return new DbQueryResult($rows);
            }

            // Fallback em memória pura caso pdo_sqlite não esteja disponível
            $status = $params['status'] ?? ($params[0] ?? 'concluido');
            $rows = array_values(array_filter(
                self::$mockTables['vendas_sql'] ?? [],
                fn($v) => ($v['status'] ?? '') === $status
            ));

            return new DbQueryResult($rows);
        }
    }

    class DbQueryResult implements IteratorAggregate
    {
        public function __construct(private array $rows) {}

        public function linq(): LinqPHP
        {
            return LinqPHP::from($this->rows);
        }

        public function toArray(): array
        {
            return $this->rows;
        }

        public function getIterator(): Traversable
        {
            return new ArrayIterator($this->rows);
        }
    }
}
