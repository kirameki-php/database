<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Info\Statements\ColumnsInfoStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\PrimaryKeyConstraint;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use stdClass;
use function array_keys;
use function implode;
use function in_array;
use function pow;

class SqliteSchemaSyntax extends SchemaSyntax
{
    /**
     * @param PrimaryKeyConstraint $constraint
     * @return string
     */
    public function formatCreateTablePrimaryKeyPart(PrimaryKeyConstraint $constraint): string
    {
        $pkParts = array_keys($constraint->columns);
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return '';
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatColumnType(ColumnDefinition $def): string
    {
        $name = $def->name;
        $type = $def->type;
        $size = $def->size;

        if ($type === 'int') {
            $ddl = 'INTEGER';
            $size ??= 8;
            if ($size === 8) {
                return $ddl;
            }
            if (!in_array($size, [2, 4], true)) {
                throw new LogicException("Invalid int size for {$name}. Expected: [2, 4, 8]. Got: {$size}.", [
                    'column' => $name,
                    'size' => $size,
                ]);
            }
            $min = pow(-2, 8 * $size);
            $max = pow(2, 8 * $size) - 1;
            $ddl .= " CHECK ({$this->asIdentifier($name)} BETWEEN {$min} AND {$max})";
            return $ddl;
        }
        if ($type === 'float') {
            return 'REAL';
        }
        if ($type === 'decimal') {
            return 'NUMERIC';
        }
        if ($type === 'bool') {
            return "BOOLEAN CHECK ({$this->asIdentifier($name)} IN (TRUE, FALSE))";
        }
        if ($type === 'string') {
            $ddl = 'TEXT';
            if ($size !== null) {
                $ddl .= " CHECK (length({$this->asIdentifier($name)}) <= $size)";
            }
            return $ddl;
        }
        if ($type === 'uuid') {
            return "UUID_TEXT CHECK (length({$this->asIdentifier($name)}) = 36)";
        }
        if ($type === 'json') {
            return 'JSON_TEXT CHECK (json_valid(' . $this->asIdentifier($name) . '))';
        }
        if ($type === 'binary') {
            return 'BLOB';
        }
        if ($type === null) {
            throw new RuntimeException('Definition type cannot be set to null');
        }

        throw new LogicException("Unknown column type: {$type} for {$name}", [
            'column' => $name,
            'type' => $type,
        ]);
    }

    /**
     * @param ListTablesStatement $statement
     * @return string
     */
    public function compileListTablesStatement(ListTablesStatement $statement): string
    {
        return "SELECT \"name\" FROM \"sqlite_master\" WHERE type = 'table'";
    }

    /**
     * @param ColumnsInfoStatement $statement
     * @return string
     */
    public function compileColumnsInfoStatement(ColumnsInfoStatement $statement): string
    {
        $columns = implode(', ', [
            'name',
            'type',
            'NOT "notnull" as `nullable`',
            '(cid + 1) as `position`',
        ]);
        return "SELECT {$columns}"
            . " FROM pragma_table_info({$this->asIdentifier($statement->table)})"
            . " ORDER BY \"cid\" ASC";
    }

    /**
     * @param iterable<int, stdClass> $rows
     * @return Iterator<int, stdClass>
     */
    public function normalizeColumnInfoStatement(iterable $rows): Iterator
    {
        foreach ($rows as $row) {
            $row->type = match ($row->type) {
                'INTEGER' => 'int',
                'REAL' => 'float',
                'NUMERIC' => 'decimal',
                'BOOLEAN' => 'bool',
                'TEXT' => 'string',
                'DATETIME' => 'datetime',
                'UUID_TEXT' => 'uuid',
                'JSON_TEXT' => 'json',
                'BLOB' => 'binary',
                default => throw new LogicException('Unsupported column type: ' . $row->type, [
                    'type' => $row->type,
                ]),
            };
            $row->nullable = (bool) $row->nullable;
            yield $row;
        }
    }
    /**
     * @param TruncateTableStatement $statement
     * @return string
     */
    public function formatTruncateTableStatement(TruncateTableStatement $statement): string
    {
        return "DELETE FROM {$this->asIdentifier($statement->table)};";
    }

    /**
     * @inheritDoc
     */
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
