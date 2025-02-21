<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\Statements\Index\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\Index\DropIndexBuilder;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\Table\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\Table\DropTableBuilder;
use Kirameki\Database\Schema\Statements\Table\RenameTableBuilder;
use Kirameki\Database\Schema\Statements\Table\TruncateTableStatement;
use Kirameki\Event\EventEmitter;
use Random\Randomizer;

class SchemaHandler
{
    public Randomizer $randomizer {
        get => $this->randomizer ??= new Randomizer();
        set => $this->randomizer = $value;
    }

    /**
     * @param Connection $connection
     * @param EventEmitter|null $events
     * @param Randomizer|null $randomizer
     */
    public function __construct(
        public readonly Connection $connection,
        protected readonly ?EventEmitter $events = null,
        ?Randomizer $randomizer = null,
    )
    {
        if ($randomizer !== null) {
            $this->randomizer = $randomizer;
        }
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
        return new RenameTableBuilder($this)->rename($from, $to);
    }

    /**
     * @return RenameTableBuilder
     */
    public function renameTables(): RenameTableBuilder
    {
        return new RenameTableBuilder($this);
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
     * @return SchemaResult<TSchemaStatement>
     */
    public function execute(SchemaStatement $statement): SchemaResult
    {
        $this->preprocess($statement);
        $result = $this->connection->adapter->runSchema($statement);
        return $this->postprocess($result);
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @return string
     */
    public function toDdl(SchemaStatement $statement): string
    {
        return $statement->toDdl($this->connection->adapter->schemaSyntax);
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
        $this->events?->emit(new SchemaExecuted($this->connection, $result));
        return $result;
    }
}
