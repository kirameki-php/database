<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\SchemaHandler;
use Kirameki\Database\Statements\Schema\Support\AlterType;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

/**
 * @extends SchemaBuilder<AlterTableStatement>
 */
class AlterTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        protected SchemaSyntax $syntax,
        string $table,
    )
    {
        parent::__construct(new AlterTableStatement($syntax, $table));
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function addColumn(string $name): ColumnBuilder
    {
        $action = new AlterColumnAction(AlterType::Add, new ColumnDefinition($name));
        $this->statement->addAction($action);
        return new AlterColumnBuilder($action);
    }

    /**
     * @param string $name
     * @return AlterColumnBuilder
     */
    public function modifyColumn(string $name): AlterColumnBuilder
    {
        $action = new AlterColumnAction(AlterType::Modify, new ColumnDefinition($name));
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
        $builder = new CreateIndexBuilder($this->syntax, $this->statement->table);
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
        $builder = new DropIndexBuilder($this->syntax, $this->statement->table);
        $builder->columns($columns);
        $this->statement->addAction($builder->statement);
        return $builder;
    }
}
