<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Iterator;
use Kirameki\Database\Query\Execution;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use LogicException;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;
use function preg_match;

/**
 * @template TConfig of DatabaseConfig
 */
abstract class PdoAdapter implements DatabaseAdapter
{
    /**
     * @param TConfig $config
     * @param PDO|null $pdo
     */
    public function __construct(
        protected DatabaseConfig $config,
        protected ?PDO $pdo = null,
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
        $affected = $this->getPdo()->exec($statement) ?: 0;
        $count = static fn () => $affected;
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        return new Execution($this, $statement, [], [], $count, $execTimeMs, null);
    }

    /**
     * @inheritDoc
     */
    public function query(string $statement, iterable $bindings = []): Execution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement, $bindings);
        $afterExecTime = hrtime(true);
        $execTimeMs = ($afterExecTime - $startTime) / 1_000_000;
        $rows = $prepared->fetchAll(PDO::FETCH_ASSOC);
        $fetchTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return new Execution($this, $statement, $bindings, $rows, $count, $execTimeMs, $fetchTimeMs);
    }

    /**
     * @inheritDoc
     */
    public function cursor(string $statement, iterable $bindings = []): Execution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement, $bindings);
        $iterator = (function() use ($prepared): Iterator {
            while (true) {
                $data = $prepared->fetch();
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
        return new Execution($this, $statement, $bindings, $iterator, $count, $execTimeMs, null);
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
        }
        catch (Throwable) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    abstract public function getQueryFormatter(): QueryFormatter;

    /**
     * @inheritDoc
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return new SchemaFormatter();
    }

    /**
     * @param string $statement
     * @param iterable<array-key, mixed> $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, iterable $bindings): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement);
        $prepared->execute(iterator_to_array($bindings));
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
     * @param string $str
     * @return string
     */
    protected function alphanumeric(string $str): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $str)) {
            throw new LogicException("Invalid string: '$str' Only alphanumeric characters, '_', and '-' are allowed.");
        }
        return $str;
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
