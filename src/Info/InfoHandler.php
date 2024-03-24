<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use stdClass;

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

        $result = $connection->query()->select('*')
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA', $connection->adapter->getConfig()->getDatabase())
            ->where('TABLE_NAME', $name)
            ->orderByAsc('ORDINAL_POSITION')
            ->execute();

        $columns = [];
        foreach ($result as $row) {
            $columnName = $row->COLUMN_NAME;
            $columns[$columnName] = new ColumnInfo(
                $columnName,
                $row->DATA_TYPE,
                $row->IS_NULLABLE === 'YES',
                $row->COLUMN_DEFAULT,
                $row->CHARACTER_MAXIMUM_LENGTH,
                $row->NUMERIC_PRECISION,
                $row->NUMERIC_SCALE,
            );
        }

        return new TableInfo($name, new Map($columns));
    }
}
