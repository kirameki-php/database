<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\NullOrder;
use Kirameki\Database\Query\Support\Ordering;
use Override;
use function implode;

class SqliteQuerySyntax extends QuerySyntax
{
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
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
        ]);
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
    public function compileListTables(ListTablesStatement $statement): Executable
    {
        return $this->toExecutable($statement, "SELECT \"name\" FROM \"sqlite_master\" WHERE type = 'table'");
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function compileListColumns(ListColumnsStatement $statement): Executable
    {
        $columns = implode(', ', [
            'name',
            'type',
            'NOT "notnull" as `nullable`',
            '(cid + 1) as `position`',
        ]);
        $table = $this->asIdentifier($statement->table);
        $template = "SELECT {$columns} FROM pragma_table_info({$table}) ORDER BY \"cid\" ASC";
        return $this->toExecutable($statement, $template);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListColumns(iterable $rows): Iterator
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
    public function compileListIndexes(ListIndexesStatement $statement): Executable
    {
        $table = $this->asLiteral($statement->table);
        return $this->toExecutable($statement, implode(' ', [
            'SELECT "PRIMARY" AS "name", group_concat(col) AS "columns", "primary" AS "type"',
            'FROM (SELECT "name" AS "col" FROM pragma_table_info(' . $table . ') WHERE "pk" > 0 ORDER BY "pk", "cid")',
            'GROUP BY "name"',
            'UNION',
            'SELECT "name", group_concat(col) AS "columns", (CASE WHEN "origin" = "pk" THEN \'primary\' WHEN "unique" > 0 THEN \'unique\' ELSE \'index\' END) AS "type"',
            'FROM (SELECT il.*, ii.name AS col FROM pragma_index_list(' . $table . ') il, pragma_index_info(il.name) ii ORDER BY il.seq, ii.seqno)',
            'GROUP BY "name"',
        ]));
    }

}
