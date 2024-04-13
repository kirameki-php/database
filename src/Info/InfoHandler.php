<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use stdClass;
use function dump;
use function explode;

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
        $syntax = $connection->adapter->getQuerySyntax();
        return $connection->query()
            ->execute(new ListTablesStatement($syntax))
            ->map(fn(stdClass $row) => $row->name);
    }

    /**
     * @param string $name
     * @return TableInfo
     */
    public function getTable(string $name): TableInfo
    {
        $connection = $this->connection;
        $syntax = $connection->adapter->getQuerySyntax();

        $columns = $connection->query()
            ->execute(new ListColumnsStatement($syntax, $name))
            ->map(fn(stdClass $r) => new ColumnInfo($r->name, $r->type, $r->nullable, $r->position))
            ->keyBy(fn(ColumnInfo $c) => $c->name);

        $indexes = $connection->query()
            ->execute(new ListIndexesStatement($syntax, $name))
            ->map(fn(stdClass $r) => new IndexInfo($r->name, explode(',', $r->columns), $r->type));

        return new TableInfo($name, $columns, $indexes);
    }
}
