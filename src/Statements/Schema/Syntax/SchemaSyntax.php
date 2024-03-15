<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Core\Value;
use Kirameki\Database\Statements\Schema\AlterColumnAction;
use Kirameki\Database\Statements\Schema\AlterDropColumnAction;
use Kirameki\Database\Statements\Schema\AlterRenameColumnAction;
use Kirameki\Database\Statements\Schema\ColumnDefinition;
use Kirameki\Database\Statements\Schema\CreateIndexStatement;
use Kirameki\Database\Statements\Schema\CreateTableStatement;
use Kirameki\Database\Statements\Schema\DropIndexStatement;
use Kirameki\Database\Statements\Schema\DropTableStatement;
use Kirameki\Database\Statements\Schema\Expressions\Expression;
use Kirameki\Database\Statements\Schema\RenameTableStatement;
use Kirameki\Database\Statements\Schema\TruncateTableStatement;
use Kirameki\Database\Statements\Syntax;
use function array_filter;
use function array_keys;
use function array_merge;
use function implode;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function strtoupper;

class SchemaSyntax extends Syntax
{
    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    public function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE TABLE';
        $parts[] = $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $definition) {
            $columnParts[] = $this->formatColumnDefinition($definition);
        }
        $pkParts = [];
        foreach (($statement->primaryKey?->columns ?? []) as $column => $order) {
            $pkParts[] = "$column $order";
        }
        if (!empty($pkParts)) {
            $columnParts[] = 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        $parts[] = '(' . implode(', ', $columnParts) . ')';
        return implode(' ', $parts).';';
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
        $parts[] = $def->name;
        $parts[] = $this->formatColumnType($def);
        if (!$def->nullable) {
            $parts[] = 'NOT NULL';
        }
        if ($def->default !== null) {
            $parts[] = 'DEFAULT '.$this->formatDefaultValue($def);
        }
        if ($def->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }
        if ($def->comment !== null) {
            $parts[] = 'COMMENT '.$this->asLiteral($def->comment);
        }
        return implode(' ', $parts);
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatColumnType(ColumnDefinition $def): string
    {
        if ($def->type === 'int') {
            return match ($def->size) {
                1 => 'TINYINT',
                2 => 'SMALLINT',
                4 => 'INT',
                8, null => 'BIGINT',
                default => throw new LogicException("Invalid int size: {$def->size} for {$def->name}", [
                    'column' => $def->name,
                    'size' => $def->size,
                ]),
            };
        }
        if ($def->type === 'decimal') {
            $args = Arr::without([$def->size, $def->scale], null);
            return 'DECIMAL' . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
        }
        if ($def->type === 'datetime') {
            $def->size ??= 6;
            return 'DATETIME(' . $def->size . ')';
        }
        if ($def->type === 'string') {
            $def->size ??= 191;
            return 'VARCHAR(' . $def->size . ')';
        }
        if ($def->type === 'uuid') {
            return 'VARCHAR(36)';
        }
        if ($def->type === null) {
            throw new RuntimeException('Definition type cannot be set to null');
        }

        $args = Arr::without([$def->size, $def->scale], null);
        return strtoupper($def->type) . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
    }

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

        if ($value instanceof Expression) {
            return $value->prepare($this);
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
}
