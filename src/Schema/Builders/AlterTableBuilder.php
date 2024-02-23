<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Database\Schema\Support\AlterType;

/**
 * @property AlterTableStatement $statement
 */
class AlterTableBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new AlterTableStatement($table);
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
     * @param string|array<string> $columns
     * @return CreateIndexBuilder
     */
    public function createIndex(string|array $columns = []): CreateIndexBuilder
    {
        $statement = new CreateIndexStatement($this->statement->table);
        $builder = new CreateIndexBuilder($this->connection, $statement);
        $builder->columns($columns);
        $this->statement->addAction($statement);
        return $builder;
    }

    /**
     * @param string|array<string> $columns
     * @return DropIndexBuilder
     */
    public function dropIndex(string|array $columns = []): DropIndexBuilder
    {
        $statement = new DropIndexStatement($this->statement->table);
        $builder = new DropIndexBuilder($this->connection, $statement);
        $builder->columns($columns);
        $this->statement->addAction($builder);
        return $builder;
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        $statements = [];
        foreach ($this->statement->actions as $action) {
            if ($action instanceof AlterColumnAction) {
                $statements[] = $formatter->formatAlterColumnAction($action);
            }
            elseif ($action instanceof AlterDropColumnAction) {
                $statements[] = $formatter->formatDropColumnAction($action);
            }
            elseif ($action instanceof AlterRenameColumnAction) {
                $statements[] = $formatter->formatRenameColumnAction($action);
            }
            elseif ($action instanceof CreateIndexStatement) {
                $statements[] = $formatter->formatCreateIndexStatement($action);
            }
            elseif ($action instanceof DropIndexStatement) {
                $statements[] = $formatter->formatDropIndexStatement($action);
            }
        }
        return $statements;
    }
}