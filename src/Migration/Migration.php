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
    public string $connection;

    /**
     * @var list<SchemaResult<covariant SchemaStatement>>|null
     */
    protected ?array $schemaResults = null;

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
    abstract protected function up(): void;

    /**
     * @return void
     */
    abstract protected function down(): void;

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runUp(): array
    {
        return $this->captureResults($this->up(...));
    }

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runDown(): array
    {
        return $this->captureResults($this->down(...));
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->db->use($this->connection);
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
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    protected function captureResults(Closure $callback): array
    {
        $this->schemaResults = [];
        $callback();
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
}
