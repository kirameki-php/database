<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Schema\Support\AlterType;

/**
 * @extends SchemaBuilder<AlterTableStatement>
 */
class AlterTableBuilder extends SchemaBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        string $table,
    )
    {
        parent::__construct($connection, new AlterTableStatement($table));
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
        $builder = new CreateIndexBuilder($this->connection, $this->statement->table);
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
        $builder = new DropIndexBuilder($this->connection, $this->statement->table);
        $builder->columns($columns);
        $this->statement->addAction($builder->statement);
        return $builder;
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $syntax = $this->connection->getSchemaSyntax();
        $statements = [];
        foreach ($this->statement->actions as $action) {
            if ($action instanceof AlterColumnAction) {
                $statements[] = $syntax->formatAlterColumnAction($action);
            }
            elseif ($action instanceof AlterDropColumnAction) {
                $statements[] = $syntax->formatDropColumnAction($action);
            }
            elseif ($action instanceof AlterRenameColumnAction) {
                $statements[] = $syntax->formatRenameColumnAction($action);
            }
            elseif ($action instanceof CreateIndexStatement) {
                $statements[] = $syntax->formatCreateIndexStatement($action);
            }
            elseif ($action instanceof DropIndexStatement) {
                $statements[] = $syntax->formatDropIndexStatement($action);
            }
        }
        return $statements;
    }
}