<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use Iterator;
use Kirameki\Database\Query\Execution;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use function hrtime;
use function implode;
use function iterator_to_array;

/**
 * @template TConfig of DatabaseConfig
 */
abstract class PdoAdapter implements DatabaseAdapter
{
    /**
     * @param TConfig $config
     * @param PDO|null $pdo
     * @param QueryFormatter|null $queryFormatter
     * @param SchemaFormatter|null $schemaFormatter
     */
    public function __construct(
        protected DatabaseConfig $config,
        protected ?PDO $pdo = null,
        protected ?QueryFormatter $queryFormatter = null,
        protected ?SchemaFormatter $schemaFormatter = null,
    )
    {
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->config = clone $this->config;
    }

    /**
     * @inheritDoc
     * @return TConfig
     */
    public function getConfig(): DatabaseConfig
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function connect(): static
    {
        $this->pdo = $this->createPdo();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $statement): Execution
    {
        $startTime = hrtime(true);
        $count = $this->getPdo()->exec($statement) ?: 0;
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        return $this->instantiateExecution($statement, [], [], $execTimeMs, $count);
    }

    /**
     * @inheritDoc
     */
    public function query(string $statement, iterable $bindings = []): Execution
    {
        $startTime = hrtime(true);
        $bindings = iterator_to_array($bindings);
        $prepared = $this->execQuery($statement, $bindings);
        $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
        $fetchTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return $this->instantiateExecution($statement, $bindings, $rows, $fetchTimeMs, $count);
    }

    /**
     * @inheritDoc
     */
    public function cursor(string $statement, iterable $bindings = []): Execution
    {
        $startTime = hrtime(true);
        $bindings = iterator_to_array($bindings);
        $prepared = $this->execQuery($statement, $bindings);
        $iterator = (function() use ($prepared): Iterator {
            while (true) {
                $data = $prepared->fetch(PDO::FETCH_OBJ);
                if ($data === false) {
                    if ($prepared->errorCode() === '00000') {
                        break;
                    }
                    $this->throwException($prepared);
                }
                yield $data;
            }
        })();
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return $this->instantiateExecution($statement, $bindings, $iterator, $execTimeMs, $count);
    }

    /**
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @inheritDoc
     */
    public function tableExists(string $table): bool
    {
        try {
            $this->query("SELECT 1 FROM $table LIMIT 1");
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return $this->queryFormatter ??= $this->instantiateQueryFormatter();
    }

    /**
     * @return QueryFormatter
     */
    abstract protected function instantiateQueryFormatter(): QueryFormatter;

    /**
     * @inheritDoc
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->schemaFormatter ??= $this->instantiateSchemaFormatter();
    }

    /**
     * @return SchemaFormatter
     */
    protected function instantiateSchemaFormatter(): SchemaFormatter
    {
        return new SchemaFormatter();
    }

    /**
     * @param string $statement
     * @param array<array-key, mixed> $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, array $bindings): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement);
        $prepared->execute($bindings);
        return $prepared;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }
        return $this->pdo;
    }

    abstract protected function createPdo(): PDO;

    /**
     * @param string $statement
     * @param array<array-key, mixed> $bindings
     * @param iterable<int, mixed> $rowIterator
     * @param float $elapsedMs
     * @param int|Closure(): int $affectedRowCount
     * @return Execution
     */
    protected function instantiateExecution(
        string $statement,
        array $bindings,
        iterable $rowIterator,
        float $elapsedMs,
        int|Closure $affectedRowCount,
    ): Execution
    {
        return new Execution($this->config, $statement, $bindings, $rowIterator, $elapsedMs, $affectedRowCount);
    }

    /**
     * @param PDOStatement $statement
     * @return void
     */
    protected function throwException(PDOStatement $statement): void
    {
        throw new RuntimeException(implode(' | ', $statement->errorInfo()));
    }
}
