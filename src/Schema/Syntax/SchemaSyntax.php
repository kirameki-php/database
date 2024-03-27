<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Schema\Expressions\DefaultValue;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Database\Schema\Statements\DropTableStatement;
use Kirameki\Database\Schema\Statements\PrimaryKeyConstraint;
use Kirameki\Database\Schema\Statements\RenameTableStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Database\Syntax;
use function array_filter;
use function array_keys;
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
     * @return string
     */
    public function formatCreateTableStatement(CreateTableStatement $statement): string
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
    public function formatCreateTablePrimaryKeyPart(PrimaryKeyConstraint $constraint): string
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
     * @param AlterColumnAction $action
     * @return string
     */
    public function formatAlterColumnAction(AlterColumnAction $action): string
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
    public function formatDropColumnAction(AlterDropColumnAction $action): string
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
    public function formatRenameColumnAction(AlterRenameColumnAction $action): string
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
     * @return string
     */
    public function formatRenameTableStatement(RenameTableStatement $statement): string
    {
        return implode(' ', [
            'ALTER TABLE',
            $this->asIdentifier($statement->from),
            'RENAME TO',
            $this->asIdentifier($statement->to),
        ]);
    }

    /**
     * @param DropTableStatement $statement
     * @return string
     */
    public function formatDropTableStatement(DropTableStatement $statement): string
    {
        return implode(' ', [
            'DROP TABLE',
            $this->asIdentifier($statement->table),
        ]);
    }

    /**
     * @param CreateIndexStatement $statement
     * @return string
     */
    public function formatCreateIndexStatement(CreateIndexStatement $statement): string
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
     * @return string
     */
    public function formatDropIndexStatement(DropIndexStatement $statement): string
    {
        $name = $statement->name ?? implode('_', array_merge([$statement->table], $statement->columns));
        return implode(' ', [
            'DROP INDEX',
            $this->asIdentifier($name),
            'ON',
            $this->asIdentifier($statement->table),
        ]);
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = $this->asIdentifier($def->name);
        $parts[] = $this->formatColumnType($def);
        if (!$def->nullable) {
            $parts[] = 'NOT NULL';
        }
        if ($def->default !== null) {
            $parts[] = 'DEFAULT '.$this->formatDefaultValue($def);
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
     * @param TruncateTableStatement $statement
     * @return string
     */
    public function formatTruncateTableStatement(TruncateTableStatement $statement): string
    {
        return implode(' ', [
            'TRUNCATE TABLE',
            $this->asIdentifier($statement->table),
        ]);
    }

    /**
     * @return string
     */
    public function formatUuid(): string
    {
        return 'UUID()';
    }

    /**
     * @return bool
     */
    abstract public function supportsDdlTransaction(): bool;
}
