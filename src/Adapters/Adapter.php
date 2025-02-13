<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\ConnectionException;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Exceptions\TransactionException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Query\TypeCastRegistry;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Transaction\TransactionOptions;
use Throwable;
use function hrtime;

/**
 * @template TConnectionConfig of ConnectionConfig
 */
abstract class Adapter
{
    /**
     * @var string
     */
    protected string $identifierDelimiter = '"';

    /**
     * @var string
     */
    protected string $literalDelimiter = "'";

    /**
     * @var string
     */
    protected string $dateTimeFormat = 'Y-m-d\TH:i:s.up';

    /**
     * @param DatabaseConfig $databaseConfig
     * @param TConnectionConfig $connectionConfig
     * @param TypeCastRegistry|null $casters
     * @param QuerySyntax|null $querySyntax
     * @param SchemaSyntax|null $schemaSyntax
     */
    public function __construct(
        public readonly DatabaseConfig $databaseConfig,
        public readonly ConnectionConfig $connectionConfig,
        protected ?TypeCastRegistry $casters = null,
        protected ?QuerySyntax $querySyntax = null,
        protected ?SchemaSyntax $schemaSyntax = null,
    )
    {
    }

    /**
     * @param bool $ifNotExist
     * @return void
     */
    abstract public function createDatabase(bool $ifNotExist = true): void;

    /**
     * @param bool $ifExist
     * @return void
     */
    abstract public function dropDatabase(bool $ifExist = true): void;

    /**
     * @return bool
     */
    abstract public function databaseExists(): bool;

    /**
     * @return bool
     */
    abstract public function isConnected(): bool;

    /**
     * @return $this
     */
    abstract public function connect(): static;

    /**
     * @return $this
     */
    abstract public function disconnect(): static;

    /**
     * @return bool
     */
    abstract public function inTransaction(): bool;

    /**
     * @param TransactionOptions|null $options
     * @return void
     */
    abstract public function beginTransaction(?TransactionOptions $options = null): void;

    /**
     * @return void
     */
    abstract public function rollback(): void;

    /**
     * @return void
     */
    abstract public function commit(): void;

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @return SchemaResult<TSchemaStatement>
     */
    abstract public function runSchema(SchemaStatement $statement): SchemaResult;

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    abstract public function runQuery(QueryStatement $statement): QueryResult;

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    abstract public function runQueryWithCursor(QueryStatement $statement): QueryResult;

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    abstract public function explainQuery(QueryStatement $statement): QueryResult;

    /**
     * @return TypeCastRegistry
     */
    public function getTypeCasterRegistry(): TypeCastRegistry
    {
        return $this->casters ??= $this->instantiateTypeCasterRegistry();
    }

    /**
     * @return TypeCastRegistry
     */
    protected function instantiateTypeCasterRegistry(): TypeCastRegistry
    {
        return new TypeCastRegistry();
    }

    /**
     * @return QuerySyntax
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
     * @return SchemaSyntax
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
     * @param Throwable $e
     * @return never
     */
    protected function throwConnectionException(Throwable $e): never
    {
        throw new ConnectionException($e->getMessage(), $this->connectionConfig, $e);
    }

    /**
     * @param Throwable $e
     * @param SchemaStatement $statement
     * @return never
     */
    protected function throwSchemaException(Throwable $e, SchemaStatement $statement): never
    {
        throw new SchemaException($e->getMessage(), $statement, $e);
    }

    /**
     * @param Throwable $e
     * @param QueryStatement $statement
     * @return never
     */
    protected function throwQueryException(Throwable $e, QueryStatement $statement): never
    {
        throw new QueryException($e->getMessage(), $statement, null, $e);
    }

    /**
     * @param Throwable $e
     * @return never
     */
    protected function throwTransactionException(Throwable $e): never
    {
        throw new TransactionException($e->getMessage(), $e);
    }
}
