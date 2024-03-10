<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use DateTimeInterface;
use Iterator;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Query\Formatters\QueryFormatter;
use Kirameki\Database\Statements\RawStatement;
use Kirameki\Database\Statements\Schema\Formatters\Formatter as SchemaFormatter;
use Kirameki\Database\Statements\Statement;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use function hrtime;
use function implode;

/**
 * @template TConfig of DatabaseConfig
 */
abstract class PdoAdapter implements DatabaseAdapter
{
    protected string $identifierDelimiter = '"';

    protected string $literalDelimiter = "'";

    protected string $dateTimeFormat = DateTimeInterface::RFC3339_EXTENDED;

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
    public function execute(Statement $statement): Execution
    {
        $startTime = hrtime(true);
        $count = $this->getPdo()->exec($statement->prepare()) ?: 0;
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        return $this->instantiateExecution($statement, [], $execTimeMs, $count);
    }

    /**
     * @inheritDoc
     */
    public function query(Statement $statement): Execution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement);
        $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
        $fetchTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return $this->instantiateExecution($statement, $rows, $fetchTimeMs, $count);
    }

    /**
     * @inheritDoc
     */
    public function cursor(Statement $statement): Execution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement);
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
        return $this->instantiateExecution($statement, $iterator, $execTimeMs, $count);
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
            $formatter = $this->getQueryFormatter();
            $table = $formatter->asIdentifier($table);
            $statement = new RawStatement($formatter, "SELECT 1 FROM {$table} LIMIT 1");
            $this->query($statement);
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
        return new SchemaFormatter(
            $this->identifierDelimiter,
            $this->literalDelimiter,
            $this->dateTimeFormat
        );
    }

    /**
     * @param Statement $statement
     * @return PDOStatement
     */
    protected function execQuery(Statement $statement): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement->prepare());
        $prepared->execute($statement->getParameters());
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
     * @param Statement $statement
     * @param iterable<int, mixed> $rowIterator
     * @param float $elapsedMs
     * @param int|Closure(): int $affectedRowCount
     * @return Execution
     */
    protected function instantiateExecution(
        Statement $statement,
        iterable $rowIterator,
        float $elapsedMs,
        int|Closure $affectedRowCount,
    ): Execution
    {
        return new Execution($this->config, $statement, $rowIterator, $elapsedMs, $affectedRowCount);
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
