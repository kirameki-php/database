<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ColumnsInfoStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use stdClass;

readonly class InfoHandler
{
    /**
     * @param Connection $connection
     */
    public function __construct(
        public Connection $connection,
    )
    {
    }

    /**
     * @return Vec<string>
     */
    public function getTableNames(): Vec
    {
        $connection = $this->connection;
        return $connection->query()
            ->execute(new ListTablesStatement($connection->adapter))
            ->map(fn(stdClass $row) => $row->name);
    }

    /**
     * @param string $name
     * @return TableInfo
     */
    public function getTable(string $name): TableInfo
    {
        $connection = $this->connection;
        $columns = $connection->query()
            ->execute(new ColumnsInfoStatement($connection->adapter, $name))
            ->map(fn(stdClass $r) => new ColumnInfo($r->name, $r->type, $r->nullable, $r->position))
            ->keyBy(fn(ColumnInfo $c) => $c->name);
        return new TableInfo($name, $columns);
    }
}
