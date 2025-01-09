<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\Column\AlterColumnAction;
use Kirameki\Database\Schema\Statements\Column\AlterColumnBuilder;
use Kirameki\Database\Schema\Statements\Column\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\Column\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\Column\ColumnBuilder;
use Kirameki\Database\Schema\Statements\ForeignKey\AlterDropForeignKeyAction;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyBuilder;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyConstraint;
use Kirameki\Database\Schema\Statements\Index\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\Index\DropIndexBuilder;
use Kirameki\Database\Schema\Statements\SchemaBuilder;

/**
 * @extends SchemaBuilder<AlterTableStatement>
 */
class AlterTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
    )
    {
        parent::__construct($handler, new AlterTableStatement($table));
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function addColumn(string $name): ColumnBuilder
    {
        $action = new AlterColumnAction(AlterType::Add, $name);
        $this->statement->addAction($action);
        return new AlterColumnBuilder($this->handler, $action);
    }

    /**
     * @param string $name
     * @return AlterColumnBuilder
     */
    public function modifyColumn(string $name): AlterColumnBuilder
    {
        $action = new AlterColumnAction(AlterType::Modify, $name);
        $this->statement->addAction($action);
        return new AlterColumnBuilder($this->handler, $action);
    }

    /**
     * @param string $from
     * @param string $to
     */
    public function renameColumn(string $from, string $to): void
    {
        $this->statement->addAction(new AlterRenameColumnAction($from, $to));
    }

    /**
     * @param string $column
     */
    public function dropColumn(string $column): void
    {
        $this->statement->addAction(new AlterDropColumnAction($column));
    }

    /**
     * @param string ...$column
     * @return CreateIndexBuilder
     */
    public function createIndex(string ...$column): CreateIndexBuilder
    {
        return $this->newIndexBuilder($column, false);
    }

    /**
     * @param string ...$column
     * @return CreateIndexBuilder
     */
    public function createUniqueIndex(string ...$column): CreateIndexBuilder
    {
        return $this->newIndexBuilder($column, true);
    }

    /**
     * @param iterable<array-key, string> $columns
     * @param bool $unique
     * @return CreateIndexBuilder
     */
    protected function newIndexBuilder(iterable $columns, bool $unique): CreateIndexBuilder
    {
        $builder = new CreateIndexBuilder($this->handler, $this->statement->table, $unique);
        $builder->columns($columns);
        $this->statement->addAction($builder->statement);
        return $builder;
    }

    /**
     * @param iterable<int, string> $columns
     * @return DropIndexBuilder
     */
    public function dropIndex(iterable $columns): DropIndexBuilder
    {
        $builder = new DropIndexBuilder($this->handler, $this->statement->table);
        $builder->columns($columns);
        $this->statement->addAction($builder->statement);
        return $builder;
    }

    /**
     * @param iterable<int, string> $columns
     * @param string $referencedTable
     * @param iterable<int, string> $referencedColumns
     * @return ForeignKeyBuilder
     */
    public function addForeignKey(iterable $columns, string $referencedTable, iterable $referencedColumns): ForeignKeyBuilder
    {
        $constraint = new ForeignKeyConstraint(
            Arr::values($columns),
            $referencedTable,
            Arr::values($referencedColumns),
        );
        $this->statement->addAction($constraint);
        return new ForeignKeyBuilder($constraint);
    }

    public function dropForeignKey(string $name): void
    {
        $this->statement->addAction(new AlterDropForeignKeyAction($name));
    }
}
