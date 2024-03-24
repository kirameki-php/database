<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Handlers;

use Kirameki\Collections\Map;
use Kirameki\Database\Info\ColumnInfo;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Info\TableInfo;
use function dump;

class SqliteInfoHandler extends InfoHandler
{
    /**
     * @param string $name
     * @return TableInfo
     */
    public function getTable(string $name): TableInfo
    {
        $connection = $this->connection;
        $syntax = $connection->adapter->getQuerySyntax();

        $result = $connection->query()->select('*')
            ->from('PRAGMA_TABLE_INFO(' . $syntax->asTable($name) . ')')
            ->execute();
dump($result);
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
