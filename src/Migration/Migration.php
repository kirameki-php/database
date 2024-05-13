<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Database\Schema\Statements\DropTableStatement;
use Kirameki\Database\Schema\Statements\RenameTableStatement;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;

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
     * @param bool $dryRun
     */
    public function __construct(
        protected readonly DatabaseManager $db,
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
     * @return SchemaResult<CreateTableStatement>
     */
    public function createTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->createTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(CreateTableBuilder): void $callback
     * @return SchemaResult<CreateTableStatement>
     */
    public function createTemporaryTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->createTemporaryTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(AlterTableBuilder): void $callback
     * @return SchemaResult<AlterTableStatement>
     */
    public function alterTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->alterTable($table), $callback);
    }

    /**
     * @param string $from
     * @param string $to
     * @return SchemaResult<RenameTableStatement>
     */
    public function renameTable(string $from, string $to): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->renameTable($from, $to));
    }

    /**
     * @param string $table
     * @return SchemaResult<DropTableStatement>
     */
    public function dropTable(string $table): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropTable($table));
    }

    /**
     * @param string $table
     * @param Closure(CreateIndexBuilder): void $callback
     * @return SchemaResult<CreateIndexStatement>
     */
    public function createIndex(string $table, Closure $callback): SchemaResult
    {
       return $this->apply($this->getSchemaHandler()->createIndex($table), $callback);
    }

    /**
     * @param string $table
     * @return SchemaResult<DropIndexStatement>
     */
    public function dropIndex(string $table): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropIndex($table));
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
     * @template TSchemaStatement of SchemaStatement
     * @template TSchemaBuilder of SchemaBuilder<TSchemaStatement>
     * @param TSchemaBuilder $builder
     * @param Closure(TSchemaBuilder): void|null $callback
     * @return SchemaResult<TSchemaStatement>
     */
    protected function apply(SchemaBuilder $builder, ?Closure $callback = null): SchemaResult
    {
        if ($callback !== null) {
            $callback($builder);
        }
        return $builder->execute($this->dryRun);
    }
}
