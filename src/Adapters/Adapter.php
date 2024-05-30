<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use DateTimeInterface;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\DropTableStatement;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use function hrtime;

/**
 * @template TConfig of ConnectionConfig
 */
abstract class Adapter
{
    /**
     * @var bool
     */
    protected bool $readonly = false;

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
    protected string $dateTimeFormat = DateTimeInterface::RFC3339_EXTENDED;

    /**
     * @param DatabaseConfig $databaseConfig
     * @param TConfig $connectionConfig
     * @param QuerySyntax|null $querySyntax
     * @param SchemaSyntax|null $schemaSyntax
     */
    public function __construct(
        protected readonly DatabaseConfig $databaseConfig,
        protected ConnectionConfig $connectionConfig,
        protected ?QuerySyntax $querySyntax = null,
        protected ?SchemaSyntax $schemaSyntax = null,
    )
    {
        $this->readonly = $connectionConfig->isReplica();
    }

    /**
     * @return TConfig
     */
    public function getConnectionConfig(): ConnectionConfig
    {
        return $this->connectionConfig;
    }

    /**
     * @param bool $enable
     * @return void
     */
    public function setReadOnlyMode(bool $enable): void
    {
        $this->readonly = $enable;
    }

    /**
     * @return bool
     */
    public function inReadOnlyMode(): bool
    {
        return $this->readonly;
    }

    /**
     * @return bool
     */
    public function dropProtectionEnabled(): bool
    {
        return $this->databaseConfig->dropProtection;
    }

    /**
     * @return bool
     */
    abstract public function inTransaction(): bool;

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
     * @return $this
     */
    abstract public function connect(): static;

    /**
     * @return $this
     */
    abstract public function disconnect(): static;

    /**
     * @param IsolationLevel|null $level
     * @return void
     */
    abstract public function beginTransaction(?IsolationLevel $level): void;

    /**
     * @return void
     */
    abstract public function rollback(): void;

    /**
     * @return void
     */
    abstract public function commit(): void;

    /**
     * @return bool
     */
    abstract public function isConnected(): bool;

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
     * @param SchemaStatement $statement
     * @return void
     */
    protected function ensureSchemaIsNonDropping(SchemaStatement $statement): void
    {
        if ($statement instanceof DropTableStatement) {
            throw new SchemaException("Drop Protection Enabled: {$statement->table} cannot be dropped.", $statement);
        }

        if ($statement instanceof TruncateTableStatement) {
            throw new SchemaException("Drop Protection Enabled: {$statement->table} cannot be truncated.", $statement);
        }
    }
}
