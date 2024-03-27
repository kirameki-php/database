<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Info\Statements\ColumnsInfoStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
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
        return "SELECT " . implode(',', [
            '"name" as "column"',
            '"type"',
            'NOT "notnull" as "nullable"',
            'dflt_value as "default"',
            'CASE "type" WHEN \'BIGINT\' THEN 8 ELSE 0 END as "length"',
        ]) . " FROM pragma_table_info({$this->asIdentifier($statement->table)})"
            . " ORDER BY \"cid\" ASC";
    }
}
