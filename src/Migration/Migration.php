<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use Kirameki\Event\EventManager;

/**
 * @consistent-constructor
 */
abstract class Migration
{
    /**
     * @var string
     */
    protected string $connection;

    /**
     * @var list<string>
     */
    protected array $executedSchemas = [];

    /**
     * @param DatabaseManager $db
     * @param EventManager $events
     * @param bool $dryRun
     */
    public function __construct(
        protected readonly DatabaseManager $db,
        protected readonly EventManager $events,
        protected bool $dryRun,
    )
    {
    }

    /**
     * @return void
     */
    abstract public function up(): void;

    /**
     * @return void
     */
    abstract public function down(): void;

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->db->use($this->connection);
    }

    /**
     * @param string $table
     * @param Closure(CreateTableBuilder): void $callback
     * @return void
     */
    public function createTable(string $table, Closure $callback): void
    {
        $this->apply($this->getSchemaHandler()->createTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(CreateTableBuilder): void $callback
     * @return void
     */
    public function createTemporaryTable(string $table, Closure $callback): void
    {
        $this->apply($this->getSchemaHandler()->createTemporaryTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(AlterTableBuilder): void $callback
     * @return void
     */
    public function alterTable(string $table, Closure $callback): void
    {
        $this->apply($this->getSchemaHandler()->alterTable($table), $callback);
    }

    /**
     * @param string $from
     * @param string $to
     * @return void
     */
    public function renameTable(string $from, string $to): void
    {
        $this->apply($this->getSchemaHandler()->renameTable($from, $to));
    }

    /**
     * @param string $table
     * @return void
     */
    public function dropTable(string $table): void
    {
        $this->apply($this->getSchemaHandler()->dropTable($table));
    }

    /**
     * @param string $table
     * @param Closure(CreateIndexBuilder): void $callback
     * @return void
     */
    public function createIndex(string $table, Closure $callback): void
    {
        $this->apply($this->getSchemaHandler()->createIndex($table), $callback);
    }

    /**
     * @param string $table
     * @return void
     */
    public function dropIndex(string $table): void
    {
        $this->apply($this->getSchemaHandler()->dropIndex($table));
    }

    /**
     * @return list<string>
     */
    public function getExecutedCommands(): array
    {
        return $this->executedSchemas;
    }

    /**
     * @return SchemaHandler
     */
    protected function getSchemaHandler(): SchemaHandler
    {
        return $this->getConnection()->schema();
    }

    /**
     * @template TSchemaBuilder of SchemaBuilder
     * @param TSchemaBuilder $builder
     * @param Closure(TSchemaBuilder): void|null $callback
     * @return void
     */
    protected function apply(SchemaBuilder $builder, ?Closure $callback = null): void
    {
        if ($callback !== null) {
            $callback($builder);
        }

        if ($this->dryRun) {
            $commands = $builder->toExecutable();
        } else {
            $result = $builder->execute();
            $this->events->emit(new SchemaExecuted($this->getConnection(), $result));
            $commands = $result->commands;
        }

        foreach ($commands as $command) {
            $this->executedSchemas[] = $command;
        }
    }
}
