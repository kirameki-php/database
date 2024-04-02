<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Iterator;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Schema\Expressions\DefaultValue;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Database\Schema\Statements\DropTableStatement;
use Kirameki\Database\Schema\Statements\PrimaryKeyConstraint;
use Kirameki\Database\Schema\Statements\RenameTableStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Database\Syntax;
use stdClass;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function implode;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

abstract class SchemaSyntax extends Syntax
{
    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    public function compileCreateTable(CreateTableStatement $statement): array
    {
        $formatted = [];
        $formatted[] = $this->formatCreateTableStatement($statement);
        foreach ($statement->indexes as $index) {
            $formatted[] = $this->compileCreateIndex($index);
        }
        return Arr::flatten($formatted);
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    protected function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE TABLE';
        $parts[] = $this->asIdentifier($statement->table);
        $columnParts = [];
        foreach ($statement->columns as $definition) {
            $columnParts[] = $this->formatColumnDefinition($definition);
        }
        if ($statement->primaryKey !== null) {
            $columnParts[] = $this->formatCreateTablePrimaryKeyPart($statement->primaryKey);
        }
        $parts[] = '(' . implode(', ', $columnParts) . ')';
        return implode(' ', $parts);
    }

    /**
     * @param PrimaryKeyConstraint $constraint
     * @return string
     */
    protected function formatCreateTablePrimaryKeyPart(PrimaryKeyConstraint $constraint): string
    {
        $pkParts = [];
        foreach ($constraint->columns as $column => $order) {
            $pkParts[] = "$column $order";
        }
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return '';
    }

    /**
     * @param AlterTableStatement $statement
     * @return list<string>
     */
    public function compileAlterTable(AlterTableStatement $statement): array
    {
        $statements = array_map(fn(object $action) => match (true) {
            $action instanceof AlterColumnAction => $this->formatAlterColumnAction($action),
            $action instanceof AlterDropColumnAction => $this->formatDropColumnAction($action),
            $action instanceof AlterRenameColumnAction => $this->formatRenameColumnAction($action),
            $action instanceof CreateIndexStatement => $this->compileCreateIndex($action),
            $action instanceof DropIndexStatement => $this->compileDropIndex($action),
            default => throw new InvalidTypeException('Unsupported action type: ' . $action::class),
        }, $statement->actions);
        return Arr::flatten($statements);
    }

    /**
     * @param AlterColumnAction $action
     * @return string
     */
    protected function formatAlterColumnAction(AlterColumnAction $action): string
    {
        $parts = [];
        $parts[] = $action->type->value;
        $parts[] = 'COLUMN';
        $parts[] = $this->formatColumnDefinition($action->definition);
        $parts[] = $action->positionType;
        $parts[] = $action->positionColumn;
        return implode(' ', array_filter($parts));
    }

    /**
     * @param AlterDropColumnAction $action
     * @return string
     */
    protected function formatDropColumnAction(AlterDropColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'DROP COLUMN';
        $parts[] = $this->asIdentifier($action->column);
        return implode(' ', $parts);
    }

    /**
     * @param AlterRenameColumnAction $action
     * @return string
     */
    protected function formatRenameColumnAction(AlterRenameColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'RENAME COLUMN';
        $parts[] = $this->asIdentifier($action->from);
        $parts[] = 'TO';
        $parts[] = $this->asIdentifier($action->to);
        return implode(' ', $parts);
    }

    /**
     * @param RenameTableStatement $statement
     * @return list<string>
     */
    public function compileRenameTable(RenameTableStatement $statement): array
    {
        return [
            "ALTER TABLE {$this->asIdentifier($statement->from)} RENAME TO {$this->asIdentifier($statement->to)}",
        ];
    }

    /**
     * @param DropTableStatement $statement
     * @return list<string>
     */
    public function compileDropTable(DropTableStatement $statement): array
    {
        return [
            "DROP TABLE {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @param CreateIndexStatement $statement
     * @return list<string>
     */
    public function compileCreateIndex(CreateIndexStatement $statement): array
    {
        return [
            $this->formatCreateIndexStatement($statement),
        ];
    }

    /**
     * @param CreateIndexStatement $statement
     * @return string
     */
    protected function formatCreateIndexStatement(CreateIndexStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE';
        if ($statement->unique) {
            $parts[] = 'UNIQUE';
        }
        $parts[] = 'INDEX';
        $parts[] = $statement->name ?? implode('_', array_merge([$statement->table], array_keys($statement->columns)));
        $parts[] = 'ON';
        $parts[] = $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $column => $order) {
            $columnParts[] = "$column $order";
        }
        $parts[] = '(' . implode(', ', $columnParts) . ')';
        if ($statement->comment !== null) {
            $parts[] = $this->asLiteral($statement->comment);
        }
        return implode(' ', $parts);
    }

    /**
     * @param DropIndexStatement $statement
     * @return list<string>
     */
    public function compileDropIndex(DropIndexStatement $statement): array
    {
        $name = $statement->name ?? implode('_', array_merge([$statement->table], $statement->columns));
        return [
            "DROP INDEX {$this->asIdentifier($name)} ON {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = $this->asIdentifier($def->name);
        $parts[] = $this->formatColumnType($def);
        if (!$def->nullable) {
            $parts[] = 'NOT NULL';
        }
        if ($def->default !== null) {
            $parts[] = 'DEFAULT ' . $this->formatDefaultValue($def);
        }
        if ($def->primaryKey === true) {
            $parts[] = 'PRIMARY KEY';
        }
        return implode(' ', $parts);
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    abstract protected function formatColumnType(ColumnDefinition $def): string;

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatDefaultValue(ColumnDefinition $def): string
    {
        $value = $def->default;

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_string($value)) {
            return $this->asLiteral($value);
        }

        if ($value instanceof DefaultValue) {
            return $value->toString($this);
        }

        throw new LogicException('Unknown default value type: ' . Value::getType($value), [
            'value' => $value,
            'column' => $def->name,
        ]);
    }

    /**
     * @param int|null $size
     * @return string
     */
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'CURRENT_TIMESTAMP' . ($size ? '(' . $size . ')' : '');
    }

    /**
     * @return string
     */
    public function formatUuid(): string
    {
        return 'UUID()';
    }

    /**
     * @param TruncateTableStatement $statement
     * @return list<string>
     */
    public function compileTruncateTable(TruncateTableStatement $statement): array
    {
        return [
            "TRUNCATE TABLE {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @param ListTablesStatement $statement
     * @return Executable
     */
    public function compileListTables(ListTablesStatement $statement): Executable
    {
        $database = $this->asLiteral($this->config->getDatabase());
        return $this->toExecutable("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = {$database}");
    }

    /**
     * @param ListColumnsStatement $statement
     * @return Executable
     */
    public function compileListColumns(ListColumnsStatement $statement): Executable
    {
        $columns = implode(', ', [
            "COLUMN_NAME AS `name`",
            "DATA_TYPE AS `type`",
            "IS_NULLABLE AS `nullable`",
            "ORDINAL_POSITION AS `position`",
        ]);
        $database = $this->asLiteral($this->config->getDatabase());
        $table = $this->asLiteral($statement->table);
        return $this->toExecutable(
            "SELECT {$columns} FROM INFORMATION_SCHEMA.COLUMNS"
            . " WHERE TABLE_SCHEMA = {$database}"
            . " AND TABLE_NAME = {$table}"
            . " ORDER BY ORDINAL_POSITION ASC",
        );
    }

    /**
     * @param iterable<int, stdClass> $rows
     * @return Iterator<int, stdClass>
     */
    public function normalizeListColumns(iterable $rows): Iterator
    {
        foreach ($rows as $row) {
            $row->type = match ($row->type) {
                'int', 'mediumint', 'tinyint', 'smallint', 'bigint' => 'integer',
                'decimal', 'float', 'double' => 'float',
                'bool' => 'bool',
                'varchar' => 'string',
                'datetime' => 'datetime',
                'json' => 'json',
                'blob' => 'binary',
                default => throw new LogicException('Unsupported column type: ' . $row->type, [
                    'type' => $row->type,
                ]),
            };
            $row->nullable = $row->nullable === 'YES';
            yield $row;
        }
    }

    /**
     * @param ListIndexesStatement $statement
     * @return Executable
     */
    public function compileListIndexes(ListIndexesStatement $statement): Executable
    {
        // TODO: Implement compileListIndexesStatement() method.
    }

    /**
     * @return bool
     */
    abstract public function supportsDdlTransaction(): bool;
}
