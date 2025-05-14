<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Functions\Syntax\SqliteFunctionSyntax;
use Kirameki\Database\Info\Statements\ColumnType;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use Kirameki\Database\Query\Statements\Lock;
use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Query\Statements\NullOrder;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;
use stdClass;
use function implode;

class SqliteQuerySyntax extends QuerySyntax
{
    use SqliteFunctionSyntax;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatFromUseIndexPart(SelectStatement $statement): string
    {
        return $statement->forceIndex !== null
            ? "INDEXED BY {$this->asIdentifier($statement->forceIndex)}"
            : '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatSelectLockPart(?Lock $lock): string
    {
        if ($lock?->option === LockOption::SkipLocked) {
            throw new LogicException('SQLite does not support SKIP LOCKED!', [
                'lock' => $lock,
            ]);
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatNullOrderingPart(string $column, Ordering $ordering): string
    {
        // Sqlite is NULL FIRST by default, so we only need to add NULLS LAST
        return match ($ordering->nulls) {
            NullOrder::First, null => '',
            NullOrder::Last => 'NULLS LAST',
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListTables(ListTablesStatement $statement): string
    {
        return "SELECT \"name\" FROM \"sqlite_master\" WHERE type = 'table'";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForTableExists(TableExistsStatement $statement): string
    {
        $table = $this->asLiteral($statement->table);
        return "SELECT 1 FROM \"sqlite_master\" WHERE type = 'table' AND \"name\" = {$table}";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListColumns(ListColumnsStatement $statement): string
    {
        $columns = $this->asCsv([
            'name',
            'type',
            'NOT "notnull" as `nullable`',
            '(cid + 1) as `position`',
        ]);
        $table = $this->asIdentifier($statement->table);
        return "SELECT {$columns} FROM pragma_table_info({$table}) ORDER BY \"cid\" ASC";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListTables(stdClass $row): ?stdClass
    {
        return $row->name !== 'sqlite_sequence'
            ? $row
            : null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListColumns(stdClass $row): ?stdClass
    {
        $row->type = match ($row->type) {
            'INTEGER' => ColumnType::Int,
            'REAL' => ColumnType::Float,
            'NUMERIC' => ColumnType::Decimal,
            'BOOLEAN' => ColumnType::Bool,
            'UUID_TEXT', 'TEXT' => ColumnType::String,
            'DATETIME' => ColumnType::Timestamp,
            'JSON_TEXT' => ColumnType::Json,
            'BLOB' => ColumnType::Blob,
            // @codeCoverageIgnoreStart
            default => throw new LogicException('Unsupported column type: ' . $row->type, [
                'type' => $row->type,
            ]),
            // @codeCoverageIgnoreEnd
        };
        $row->nullable = (bool) $row->nullable;
        return $row;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListIndexes(ListIndexesStatement $statement): string
    {
        $table = $this->asLiteral($statement->table);
        return implode(' ', [
            'SELECT "PRIMARY" AS "name", group_concat(col) AS "columns", "primary" AS "type"',
            'FROM (SELECT "name" AS "col" FROM pragma_table_info(' . $table . ') WHERE "pk" > 0 ORDER BY "pk", "cid")',
            'GROUP BY "name"',
            'UNION',
            'SELECT "name", group_concat(col) AS "columns", (CASE WHEN "origin" = "pk" THEN \'primary\' WHEN "unique" > 0 THEN \'unique\' ELSE \'index\' END) AS "type"',
            'FROM (SELECT il.*, ii.name AS col FROM pragma_index_list(' . $table . ') il, pragma_index_info(il.name) ii ORDER BY il.seq, ii.seqno)',
            'GROUP BY "name"',
        ]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListForeignKeys(ListForeignKeysStatement $statement): string
    {
        $table = $this->asLiteral($statement->table);
        $columns = $this->asCsv([
            'cast(id as text) as "name"',
            'group_concat("from") AS "columns"',
            '"table" AS "referencedTable"',
            'group_concat("to") AS "referencedColumns"',
            'on_update AS "onUpdate"',
            'on_delete AS "onDelete"',
        ]);
        return implode(' ', [
            'SELECT ' . $columns,
            'FROM pragma_foreign_key_list(' . $table . ')',
            'GROUP BY id',
            'ORDER BY id ASC, seq ASC',
        ]);
    }
}
