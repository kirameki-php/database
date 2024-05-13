<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Support\AlterType;
use function iterator_to_array;

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
        return new AlterColumnBuilder($action);
    }

    /**
     * @param string $name
     * @return AlterColumnBuilder
     */
    public function modifyColumn(string $name): AlterColumnBuilder
    {
        $action = new AlterColumnAction(AlterType::Modify, $name);
        $this->statement->addAction($action);
        return new AlterColumnBuilder($action);
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
     * @param iterable<int, string> $columns
     * @return CreateIndexBuilder
     */
    public function createIndex(iterable $columns): CreateIndexBuilder
    {
        $builder = new CreateIndexBuilder($this->statement->table);
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
        $builder = new DropIndexBuilder($this->statement->table);
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
            iterator_to_array($columns),
            $referencedTable,
            iterator_to_array($referencedColumns),
        );
        $this->statement->addAction($constraint);
        return new ForeignKeyBuilder($constraint);
    }

    public function dropForeignKey(string $name): void
    {
        $this->statement->addAction(new AlterDropForeignKeyAction($name));
    }
}
