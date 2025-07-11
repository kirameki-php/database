<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\Index\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\Index\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\Index\DropIndexStatement;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\Table\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\Table\AlterTableStatement;
use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Kirameki\Database\Schema\Statements\Table\DropTableStatement;
use Kirameki\Database\Schema\Statements\Table\RenameTableBuilder;
use Kirameki\Database\Schema\Statements\Table\RenameTableStatement;
use function basename;
use function str_replace;

/**
 * @consistent-constructor
 */
abstract class Migration
{
    protected DatabaseManager $db;

    /**
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * @var list<SchemaResult<covariant SchemaStatement>>
     */
    private array $schemaResults = [];

    /**
     * @internal For internal use only
     * @param DatabaseManager $db
     * @return static
     */
    public function setDatabaseManager(DatabaseManager $db): static
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return void
     */
    abstract protected function forward(): void;

    /**
     * @return void
     */
    abstract protected function backward(): void;

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runForward(): array
    {
        return $this->run($this->forward(...));
    }

    /**
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    public function runBackward(): array
    {
        return $this->run($this->backward(...));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return basename(str_replace('\\', '/', $this::class));
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->db->use($this->connection ?? $this->db->default);
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
     * @param Closure(RenameTableBuilder): void $callback
     * @return SchemaResult<RenameTableStatement>
     */
    protected function renameTables(Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->renameTables(), $callback);
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
        return $this->apply($this->getSchemaHandler()->createIndex($table, []), $callback);
    }

    /**
     * @param string $table
     * @param Closure(CreateIndexBuilder): void $callback
     * @return SchemaResult<CreateIndexStatement>
     */
    protected function createUniqueIndex(string $table, Closure $callback): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->createUniqueIndex($table, []), $callback);
    }

    /**
     * @param string $table
     * @param string $name
     * @return SchemaResult<DropIndexStatement>
     */
    protected function dropIndexByName(string $table, string $name): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropIndexByName($table, $name));
    }

    /**
     * @param string $table
     * @param iterable<int, string> $columns
     * @return SchemaResult<DropIndexStatement>
     */
    protected function dropIndexByColumns(string $table, iterable $columns): SchemaResult
    {
        return $this->apply($this->getSchemaHandler()->dropIndexByColumns($table, $columns));
    }

    /**
     * @param Closure(): mixed $callback
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    protected function run(Closure $callback): array
    {
        $this->schemaResults = [];

        $connection = $this->getConnection();

        $this->supportsDdlTransaction($connection)
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
        $result = $builder->execute();
        $this->schemaResults[] = $result;
        return $result;
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    protected function supportsDdlTransaction(Connection $connection): bool
    {
        return $connection->adapter->schemaSyntax->supportsDdlTransaction();
    }
}
