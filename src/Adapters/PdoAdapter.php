<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use DateTimeInterface;
use Iterator;
use Kirameki\Collections\LazyIterator;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryExecution;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\SchemaExecution;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use function dump;
use function hrtime;
use function implode;
use function iterator_to_array;

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
     * @param QuerySyntax|null $querySyntax
     * @param SchemaSyntax|null $schemaSyntax
     */
    public function __construct(
        protected DatabaseConfig $config,
        protected ?PDO $pdo = null,
        protected ?QuerySyntax $querySyntax = null,
        protected ?SchemaSyntax $schemaSyntax = null,
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
    public function disconnect(): static
    {
        $this->pdo = null;
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
    public function runSchema(SchemaStatement $statement): SchemaExecution
    {
        try {
            $startTime = hrtime(true);
            foreach ($statement->toCommands() as $schema) {
                dump($schema);
                $this->getPdo()->exec($schema);
            }
            $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
            return $this->instantiateSchemaExecution($statement, $execTimeMs);
        } catch (PDOException $e) {
            throw new SchemaException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function query(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $prepared = $this->executeQueryStatement($statement);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            if ($statement instanceof Normalizable) {
                $rows = iterator_to_array($statement->normalize($rows));
            }
            $fetchTimeMs = (hrtime(true) - $startTime) / 1_000_000;
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $fetchTimeMs, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $prepared = $this->executeQueryStatement($statement);
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
            if ($statement instanceof Normalizable) {
                $iterator = $statement->normalize($iterator);
            }
            $rows = new LazyIterator($iterator);
            $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $execTimeMs, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, $e);
        }
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
    public function getQuerySyntax(): QuerySyntax
    {
        return $this->querySyntax ??= $this->instantiateQuerySyntax();
    }

    /**
     * @return QuerySyntax
     */
    abstract protected function instantiateQuerySyntax(): QuerySyntax;

    /**
     * @inheritDoc
     */
    public function getSchemaSyntax(): SchemaSyntax
    {
        return $this->schemaSyntax ??= $this->instantiateSchemaSyntax();
    }

    /**
     * @return SchemaSyntax
     */
    abstract protected function instantiateSchemaSyntax(): SchemaSyntax;

    /**
     * @param QueryStatement $statement
     * @return PDOStatement
     */
    protected function executeQueryStatement(QueryStatement $statement): PDOStatement
    {
        $executable = $statement->prepare();
        $prepared = $this->getPdo()->prepare($executable->template);
        $prepared->execute($executable->parameters);
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

    /**
     * @return PDO
     */
    abstract protected function createPdo(): PDO;

    /**
     * @template TStatement of SchemaStatement
     * @param TStatement $statement
     * @param float $elapsedMs
     * @return SchemaExecution<TStatement>
     */
    protected function instantiateSchemaExecution(
        SchemaStatement $statement,
        float $elapsedMs,
    ): SchemaExecution
    {
        return new SchemaExecution($statement, $elapsedMs);
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @param float $elapsedMs
     * @param iterable<int, mixed> $rows
     * @param int|Closure(): int $affectedRowCount
     * @return QueryResult<TStatement>
     */
    protected function instantiateQueryResult(
        QueryStatement $statement,
        float $elapsedMs,
        iterable $rows,
        int|Closure $affectedRowCount,
    ): QueryResult
    {
        $execution = new QueryExecution($statement, $elapsedMs, $affectedRowCount);
        return new QueryResult($execution, $rows);
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
