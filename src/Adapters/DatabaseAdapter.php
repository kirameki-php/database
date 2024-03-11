<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Statement;

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
     * @param Statement $statement
     * @return Execution
     */
    public function execute(Statement $statement): Execution;

    /**
     * @param Statement $statement
     * @return Execution
     */
    public function query(Statement $statement): Execution;

    /**
     * @param Statement $statement
     * @return Execution
     */
    public function cursor(Statement $statement): Execution;

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
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool;

    /**
     * @param string $table
     * @return void
     */
    public function truncate(string $table): void;

    /**
     * @return QuerySyntax
     */
    public function getQuerySyntax(): QuerySyntax;

    /**
     * @return SchemaSyntax
     */
    public function getSchemaSyntax(): SchemaSyntax;

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool;
}
