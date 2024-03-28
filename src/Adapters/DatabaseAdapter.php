<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\SchemaExecution;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;

interface DatabaseAdapter
{
    /**
     * @return DatabaseConfig
     */
    public function getConfig(): DatabaseConfig;

    /**
     * @return $this
     */
    public function connect(): static;

    /**
     * @return $this
     */
    public function disconnect(): static;

    /**
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * @template TStatement of SchemaStatement
     * @param TStatement $statement
     * @return SchemaExecution<TStatement>
     */
    public function runSchema(SchemaStatement $statement): SchemaExecution;

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function query(QueryStatement $statement): QueryResult;

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function cursor(QueryStatement $statement): QueryResult;

    /**
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * @return void
     */
    public function rollback(): void;

    /**
     * @return void
     */
    public function commit(): void;

    /**
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * @param bool $ifNotExist
     * @return void
     */
    public function createDatabase(bool $ifNotExist = true): void;

    /**
     * @param bool $ifExist
     * @return void
     */
    public function dropDatabase(bool $ifExist = true): void;

    /**
     * @return bool
     */
    public function databaseExists(): bool;

    /**
     * @return QuerySyntax
     */
    public function getQuerySyntax(): QuerySyntax;

    /**
     * @return SchemaSyntax
     */
    public function getSchemaSyntax(): SchemaSyntax;
}
