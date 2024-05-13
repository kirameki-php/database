<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\Statements\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\DropIndexBuilder;
use Kirameki\Database\Schema\Statements\DropTableBuilder;
use Kirameki\Database\Schema\Statements\RenameTableBuilder;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Event\EventManager;

readonly class SchemaHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
    )
    {
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute(new TruncateTableStatement($table));
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return new CreateTableBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTemporaryTable(string $table): CreateTableBuilder
    {
        return new CreateTableBuilder($this, $table, true);
    }

    /**
     * @param string $table
     * @return AlterTableBuilder
     */
    public function alterTable(string $table): AlterTableBuilder
    {
        return new AlterTableBuilder($this, $table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return RenameTableBuilder
     */
    public function renameTable(string $from, string $to): RenameTableBuilder
    {
        return new RenameTableBuilder($this, $from, $to);
    }

    /**
     * @param string $table
     * @return DropTableBuilder
     */
    public function dropTable(string $table): DropTableBuilder
    {
        return new DropTableBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table): CreateIndexBuilder
    {
        return new CreateIndexBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return DropIndexBuilder
     */
    public function dropIndex(string $table): DropIndexBuilder
    {
        return new DropIndexBuilder($this, $table);
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @param bool $dryRun
     * @return SchemaResult<TSchemaStatement>
     */
    public function execute(SchemaStatement $statement, bool $dryRun = false): SchemaResult
    {
        $this->preprocess($statement);
        $result = $this->connection->adapter->runSchema($statement, $dryRun);
        return $this->postprocess($result);
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @return string
     */
    public function toString(SchemaStatement $statement): string
    {
        return $statement->toString($this->connection->adapter->getSchemaSyntax());
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     */
    protected function preprocess(SchemaStatement $statement): void
    {
        $this->connection->connectIfNotConnected();
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param SchemaResult<TSchemaStatement> $result
     * @return SchemaResult<TSchemaStatement>
     */
    protected function postprocess(SchemaResult $result): SchemaResult
    {
        $this->events->emit(new SchemaExecuted($this->connection, $result));
        return $result;
    }
}
