<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
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
        return new TableInfo($this->connection, $name);
    }
}
