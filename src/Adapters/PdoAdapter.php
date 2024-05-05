<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use DateTimeInterface;
use Iterator;
use Kirameki\Collections\LazyIterator;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use function assert;
use function dump;
use function hrtime;
use function implode;
use function iterator_to_array;

/**
 * @template TConfig of DatabaseConfig
 */
abstract class PdoAdapter implements DatabaseAdapter
{
    /**
     * @var bool
     */
    protected bool $readonly = false;

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
        public ?PDO $pdo = null,
        protected ?QuerySyntax $querySyntax = null,
        protected ?SchemaSyntax $schemaSyntax = null,
    )
    {
        $this->readonly = $config->isReplica();
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
    #[Override]
    public function getConfig(): DatabaseConfig
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function connect(): static
    {
        $this->pdo = $this->createPdo();
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function disconnect(): static
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runSchema(SchemaStatement $statement): SchemaResult
    {
        try {
            $startTime = hrtime(true);
            $executables = $statement->toExecutable($this->getSchemaSyntax());
            foreach ($executables as $executable) {
                $this->getPdo()->exec($executable);
            }
            return $this->instantiateSchemaExecution($statement, $executables, $startTime);
        } catch (PDOException $e) {
            throw new SchemaException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQuery(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            if ($statement instanceof Normalizable) {
                $rows = iterator_to_array($statement->normalize($syntax, $rows));
            }
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQueryWithCursor(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $iterator = (function() use ($prepared, $statement): Iterator {
                while (true) {
                    $data = $prepared->fetch(PDO::FETCH_OBJ);
                    if ($data === false) {
                        if ($prepared->errorCode() === '00000') {
                            break;
                        }
                        $this->throwException($prepared, $statement);
                    }
                    yield $data;
                }
            })();
            if ($statement instanceof Normalizable) {
                $iterator = $statement->normalize($syntax, $iterator);
            }
            $rows = new LazyIterator($iterator);
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function explainQuery(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = 'EXPLAIN ' . $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, 0);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function setReadOnlyMode(bool $enable): void
    {
        $this->readonly = $enable;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function inReadOnlyMode(): bool
    {
        return $this->readonly;
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
    #[Override]
    public function getSchemaSyntax(): SchemaSyntax
    {
        return $this->schemaSyntax ??= $this->instantiateSchemaSyntax();
    }

    /**
     * @return SchemaSyntax
     */
    abstract protected function instantiateSchemaSyntax(): SchemaSyntax;

    /**
     * @param string $template
     * @param list<mixed> $parameters
     * @return PDOStatement
     */
    protected function executeQueryStatement(string $template, array $parameters): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($template);
        $prepared->execute($parameters);
        return $prepared;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        $this->connect();
        assert($this->pdo !== null);
        return $this->pdo;
    }

    /**
     * @return PDO
     */
    abstract protected function createPdo(): PDO;

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @param list<string> $commands
     * @param float $startTime
     * @return SchemaResult<TSchemaStatement>
     */
    protected function instantiateSchemaExecution(
        SchemaStatement $statement,
        array $commands,
        float $startTime,
    ): SchemaResult
    {
        $elapsedMs = (hrtime(true) - $startTime) / 1_000_000;
        return new SchemaResult($statement, $commands, $elapsedMs);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     * @param float $startTime
     * @param iterable<int, mixed> $rows
     * @param int|Closure(): int $affectedRowCount
     * @return QueryResult<TQueryStatement, mixed>
     */
    protected function instantiateQueryResult(
        QueryStatement $statement,
        string $template,
        array $parameters,
        float $startTime,
        iterable $rows,
        int|Closure $affectedRowCount,
    ): QueryResult
    {
        $elapsedMs = (hrtime(true) - $startTime) / 1_000_000;
        return new QueryResult($statement, $template, $parameters, $elapsedMs, $affectedRowCount, $rows);
    }

    /**
     * @param PDOStatement $prepared
     * @param QueryStatement $statement
     * @return void
     */
    protected function throwException(PDOStatement $prepared, QueryStatement $statement): void
    {
        throw new QueryException(implode(' | ', $prepared->errorInfo()), $statement);
    }
}
