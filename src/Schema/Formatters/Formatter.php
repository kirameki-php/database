<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Formatters;

use Kirameki\Collections\Arr;
use Kirameki\Database\Schema\Expressions\CurrentTimestamp;
use Kirameki\Database\Schema\Expressions\Expr;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\BaseStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Support\Str;
use RuntimeException;
use function array_filter;
use function array_keys;
use function array_merge;
use function implode;
use function is_bool;
use function is_string;
use function str_replace;
use function strtoupper;

class Formatter
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
        $parts[] = $this->quote($action->column);
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
        $parts[] = $this->quote($action->from);
        $parts[] = 'TO';
        $parts[] = $this->quote($action->to);
        return implode(' ', $parts);
    }

    /**
     * @param string $from
     * @param string $to
     * @return string
     */
    public function formatRenameTableStatement(string $from, string $to): string
    {
        return 'ALTER TABLE '.$this->quote($from).' RENAME TO '.$this->quote($to).';';
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function formatDropTableStatement(BaseStatement $statement): string
    {
        return 'DROP TABLE '.$statement->table.';';
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
            $parts[] = $this->literalize($statement->comment);
        }
        return implode(' ', $parts) . ';';
    }

    /**
     * @param DropIndexStatement $statement
     * @return string
     */
    public function formatDropIndexStatement(DropIndexStatement $statement): string
    {
        $name = $statement->name ?? implode('_', array_merge([$statement->table], $statement->columns));
        return 'DROP INDEX '.$name.' ON '.$statement->table.';';
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
            $parts[] = 'COMMENT '.$this->literalize($def->comment);
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
                default => throw new RuntimeException('Invalid int size: '.$def->size.' for '.$def->name),
            };
        }
        if ($def->type === 'decimal') {
            $args = Arr::compact([$def->size, $def->scale]);
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

        $args = Arr::compact([$def->size, $def->scale]);
        return strtoupper($def->type) . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
    }

    /**
     * @param string $str
     * @return string
     */
    public function quote(string $str): string
    {
        $char = '`';
        return $char . str_replace($char, $char . $char, $str) . $char;
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
            return $this->literalize($value);
        }

        if ($value instanceof Expr) {
            return $value->toSql($this);
        }

        if ($value instanceof CurrentTimestamp) {
            return 'CURRENT_TIMESTAMP' . ($def->size ? '(' . $def->size . ')' : '');
        }

        throw new RuntimeException('Unknown default value type: '.Str::valueOf($value));
    }

    /**
     * @param string $str
     * @return string
     */
    public function literalize(string $str): string
    {
        return "'".str_replace("'", "''", $str)."'";
    }
}
