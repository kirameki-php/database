<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\PrimaryKeyConstraint;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Override;
use function array_keys;
use function implode;
use function in_array;
use function pow;

class SqliteSchemaSyntax extends SchemaSyntax
{
    /**
     * @inheritDoc
     */
    public function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        $formatted = parent::formatCreateTableStatement($statement);

        $hasAutoIncrementColumn = false;
        foreach ($statement->columns as $column) {
            if ($column->autoIncrement) {
                $hasAutoIncrementColumn = true;
                break;
            }
        }
        if (!$hasAutoIncrementColumn) {
            $formatted .= ' WITHOUT ROWID';
        }

        return $formatted;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $formatted = parent::formatColumnDefinition($def);

        if ($def->autoIncrement) {
            if (!$def->primaryKey) {
                throw new LogicException('Auto increment column must be the primary key.');
            }
            $formatted .= ' AUTOINCREMENT';
        }

        return $formatted;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCreateTablePrimaryKeyPart(PrimaryKeyConstraint $constraint): string
    {
        $pkParts = array_keys($constraint->columns);
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
        if ($type === 'text') {
            return 'TEXT';
        }
        if ($type === 'uuid') {
            return "TEXT CHECK (length({$this->asIdentifier($name)}) = 36)";
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
     * @inheritDoc
     */
    #[Override]
    public function compileListTablesStatement(ListTablesStatement $statement): Executable
    {
        return $this->toExecutable("SELECT \"name\" FROM \"sqlite_master\" WHERE type = 'table'");
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function compileListColumnsStatement(ListColumnsStatement $statement): Executable
    {
        $columns = implode(', ', [
            'name',
            'type',
            'NOT "notnull" as `nullable`',
            '(cid + 1) as `position`',
        ]);
        $table = $this->asIdentifier($statement->table);
        return $this->toExecutable("SELECT {$columns} FROM pragma_table_info({$table}) ORDER BY \"cid\" ASC");
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListColumnsStatement(iterable $rows): Iterator
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
     * @inheritDoc
     */
    #[Override]
    public function compileListIndexesStatement(ListIndexesStatement $statement): Executable
    {
        $table = $this->asLiteral($statement->table);
        return $this->toExecutable(implode(' ', [
            'SELECT "PRIMARY" AS "name", group_concat(col) AS "columns", "primary" AS "type"',
            'FROM (SELECT "name" AS "col" FROM pragma_table_info(' . $table . ') WHERE "pk" > 0 ORDER BY "pk", "cid")',
            'GROUP BY "name"',
            'UNION',
            'SELECT "name", group_concat(col) AS "columns", (CASE WHEN "origin" = "pk" THEN \'primary\' WHEN "unique" > 0 THEN \'unique\' ELSE \'index\' END) AS "type"',
            'FROM (SELECT il.*, ii.name AS col FROM pragma_index_list(' . $table . ') il, pragma_index_info(il.name) ii ORDER BY il.seq, ii.seqno)',
            'GROUP BY "name"',
        ]));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatTruncateTableStatement(TruncateTableStatement $statement): array
    {
        $statements = [];
        $statements[] = 'DELETE FROM "sqlite_sequence" WHERE "name" = \'' . $statement->table . '\'';
        $statements[] = "DELETE FROM {$this->asIdentifier($statement->table)}";
        return $statements;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
