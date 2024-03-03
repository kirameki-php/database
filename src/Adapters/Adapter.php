<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Configs\DatabaseConfig;
use Kirameki\Database\Query\Execution;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

interface Adapter
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
     * @param string $statement
     * @return Execution
     */
    public function execute(string $statement): Execution;

    /**
     * @param string $statement
     * @param iterable<array-key, mixed> $bindings
     * @return Execution
     */
    public function query(string $statement, iterable $bindings = []): Execution;

    /**
     * @param string $statement
     * @param iterable<array-key, mixed> $bindings
     * @return Execution
     */
    public function cursor(string $statement, iterable $bindings = []): Execution;

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
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter;

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool;
}
