<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ColumnsInfoStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use stdClass;
use function dump;

class InfoHandler
{
    /**
     * @param Connection $connection
     */
    public function __construct(
        public readonly Connection $connection,
    )
    {
    }

    /**
     * @return Vec<string>
     */
    public function getTableNames(): Vec
    {
        $connection = $this->connection;
        $statement = new ListTablesStatement($connection->adapter->getQuerySyntax());
        return $connection->query()->execute($statement)->map(fn(stdClass $row) => $row->name);
    }

    /**
     * @param string $name
     * @return TableInfo
     */
    public function getTable(string $name): TableInfo
    {
        $connection = $this->connection;
        $statement = new ColumnsInfoStatement($connection->adapter->getQuerySyntax(), $name);
        $rows = $connection->query()->execute($statement);
        dump($rows);
        $columns = [];
        foreach ($rows as $row) {
            $columns[] = new ColumnInfo(
                $row->column,
                $row->type,
                $row->nullable,
                $row->default,
            );
        }
        return new TableInfo($name, new Map($columns));
    }
}
