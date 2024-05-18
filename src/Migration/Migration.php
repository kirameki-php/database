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
     * @var string|null
     */
    public ?string $connection = null;

    /**
     * @var list<SchemaResult<covariant SchemaStatement>>|null
     */
    protected ?array $schemaResults = null;

    /**
     * @var bool
     */
    protected bool $dryRun = false;

    /**
     * @param DatabaseManager $db
     */
    public function __construct(
        protected readonly DatabaseManager $db,
    )
    {
    }

    /**
     * @return void
     */
    abstract protected function up(): void;

    /**
     * @return void
     */
    abstract protected function down(): void;

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runUp(bool $dryRun): array
    {
        $this->dryRun = $dryRun;

        return $this->run($this->up(...));
    }

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runDown(bool $dryRun): array
    {
        $this->dryRun = $dryRun;
        return $this->run($this->down(...));
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection !== null
            ? $this->db->use($this->connection)
            : $this->db->useDefault();
    }

    /**
     * @return SchemaHandler
     */
    protected function getSchemaHandler(): SchemaHandler
    {
        return $this->getConnection()->schema();
    }

    /**
     * @param string $table
     * @param Closure(CreateTableBuilder): void $callback
     * @return SchemaResult<CreateTableStatement>
     */
    protected function createTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->createTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(CreateTableBuilder): void $callback
     * @return SchemaResult<CreateTableStatement>
     */
    protected function createTemporaryTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->createTemporaryTable($table), $callback);
    }

    /**
     * @param string $table
     * @param Closure(AlterTableBuilder): void $callback
     * @return SchemaResult<AlterTableStatement>
     */
    protected function alterTable(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->alterTable($table), $callback);
    }

    /**
     * @param string $from
     * @param string $to
     * @return SchemaResult<RenameTableStatement>
     */
    protected function renameTable(string $from, string $to): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->renameTable($from, $to));
    }

    /**
     * @param string $table
     * @return SchemaResult<DropTableStatement>
     */
    protected function dropTable(string $table): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropTable($table));
    }

    /**
     * @param string $table
     * @param Closure(CreateIndexBuilder): void $callback
     * @return SchemaResult<CreateIndexStatement>
     */
    protected function createIndex(string $table, Closure $callback): SchemaResult
    {
       return $this->apply($this->getSchemaHandler()->createIndex($table), $callback);
    }

    /**
     * @param string $table
     * @return SchemaResult<DropIndexStatement>
     */
    protected function dropIndex(string $table): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropIndex($table));
    }

    /**
     * @param Closure(): mixed $callback
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    protected function run(Closure $callback): array
    {
        $this->schemaResults = [];

        $connection = $this->getConnection();

        $this->supportsTransaction($connection)
            ? $connection->transaction($callback)
            : $callback();

        return $this->schemaResults;
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
        $result = $builder->execute($this->dryRun);
        $this->schemaResults ??= [];
        $this->schemaResults[] = $result;
        return $result;
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    protected function supportsTransaction(Connection $connection): bool
    {
        return $connection->adapter->getSchemaSyntax()->supportsDdlTransaction();
    }
}
