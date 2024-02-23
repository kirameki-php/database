<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Iterator;
use Kirameki\Core\Config;
use Kirameki\Database\Query\Execution;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use LogicException;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;
use function preg_match;

abstract class PdoAdapter implements Adapter
{
    /**
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->config = clone $this->config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    public function connect(): static
    {
        $this->pdo = $this->createPdo();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @param string $statement
     * @return Execution
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
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Execution
     */
    public function query(string $statement, array $bindings = []): Execution
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
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Execution
     */
    public function cursor(string $statement, array $bindings = []): Execution
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
     * @return void
     */
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @param string $id
     */
    public function setSavepoint(string $id): void
    {
        $this->getPdo()->exec('SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @param string $id
     */
    public function rollbackSavepoint(string $id): void
    {
        $this->getPdo()->exec('ROLLBACK TO SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @param string $table
     * @return bool
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
     * @return QueryFormatter
     */
    abstract public function getQueryFormatter(): QueryFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return new SchemaFormatter();
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
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
