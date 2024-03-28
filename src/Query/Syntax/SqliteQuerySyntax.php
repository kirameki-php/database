<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Info\Statements\ColumnsInfoStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use stdClass;
use function implode;

class SqliteQuerySyntax extends QuerySyntax
{
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
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
        $columns = implode(',', [
            '"name" as "column"',
            '"type"',
            'NOT "notnull" as "nullable"',
            '"cid" as "position"',
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
                'DATETIME' => 'timestamp',
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
}
