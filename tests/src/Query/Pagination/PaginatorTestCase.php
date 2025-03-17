<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;
use stdClass;
use Tests\Kirameki\Database\Query\QueryTestCase;

class PaginatorTestCase extends QueryTestCase
{
    protected ?Connection $connection = null;

    protected function getCachedConnection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->sqliteConnection();
            $t = $this->connection->schema()->createTable('t');
            $t->id();
            $t->execute();
        }
        return $this->connection;
    }

    /**
     * @param int $size
     * @return QueryResult<SelectStatement, stdClass>
     */
    protected function createRecords(int $size): QueryResult
    {
        $tableName = 't';
        $conn = $this->getCachedConnection();

        $query = $conn->query();
        $values = [];
        for ($i = 0; $i < $size; $i++) {
            $values[] = ['id' => $i];
        }
        $query->insertInto($tableName)->values($values)->execute();
        return $query->select()->from($tableName)->execute();
    }
}
