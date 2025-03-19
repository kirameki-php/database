<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use stdClass;

class InfoHandler
{
    /**
     * @param Connection $connection
     */
    public function __construct(
        protected readonly Connection $connection,
    )
    {
    }

    /**
     * @return Vec<string>
     */
    public function getTableNames(): Vec
    {
        return $this->connection->query()
            ->execute(new ListTablesStatement())
            ->map(static fn(stdClass $row) => $row->name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function tableExists(string $name): bool
    {
        return $this->connection->query()
            ->execute(new TableExistsStatement($name))
            ->isNotEmpty();
    }

    /**
     * @param string $name
     * @return TableInfo
     */
    public function getTableInfo(string $name): TableInfo
    {
        $handler = $this->connection->query();

        $columns = $handler->execute(new ListColumnsStatement($name))
            ->map(static fn(stdClass $r) => new ColumnInfo($r->name, $r->type, $r->nullable, $r->position))
            ->keyBy(static fn(ColumnInfo $c) => $c->name);

        $indexes = $handler->execute(new ListIndexesStatement($name))
            ->map(static fn(stdClass $r) => new IndexInfo($r->name, $r->columns, $r->type));

        $foreignKeys = $handler->execute(new ListForeignKeysStatement($name))
            ->map(static fn(stdClass $r) => new ForeignKeyInfo($r->name, $r->columns, $r->referencedTable, $r->referencedColumns));

        return new TableInfo($name, $columns, $indexes, $foreignKeys);
    }
}
