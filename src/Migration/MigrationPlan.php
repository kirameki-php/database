<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\AlterTableBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\DropIndexBuilder;
use Kirameki\Database\Schema\Statements\DropTableBuilder;
use Kirameki\Database\Schema\Statements\RenameTableBuilder;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use function array_map;

class MigrationPlan
{
    /**
     * @var list<SchemaBuilder<covariant SchemaStatement>>
     */
    protected array $builders = [];

    /**
     * @param Connection $connection
     */
    public function __construct(
        public readonly Connection $connection,
    )
    {
    }

    /**
     * @return list<SchemaStatement>
     */
    public function toStatements(): array
    {
        return array_map(static fn(SchemaBuilder $b) => $b->getStatement(), $this->builders);
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[] = new CreateTableBuilder($table);
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTemporaryTable(string $table): CreateTableBuilder
    {
        return $this->builders[] = new CreateTableBuilder($table, true);
    }

    /**
     * @param string $table
     * @return AlterTableBuilder
     */
    public function alterTable(string $table): AlterTableBuilder
    {
        return $this->builders[] = new AlterTableBuilder($table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return RenameTableBuilder
     */
    public function renameTable(string $from, string $to): RenameTableBuilder
    {
        return $this->builders[] = new RenameTableBuilder($from, $to);
    }

    /**
     * @param string $table
     * @return DropTableBuilder
     */
    public function dropTable(string $table): DropTableBuilder
    {
        return $this->builders[] = new DropTableBuilder($table);
    }

    /**
     * @param string $table
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table): CreateIndexBuilder
    {
        return $this->builders[] = new CreateIndexBuilder($table);
    }

    /**
     * @param string $table
     * @return DropIndexBuilder
     */
    public function dropIndex(string $table): DropIndexBuilder
    {
        return $this->builders[] = new DropIndexBuilder($table);
    }
}
