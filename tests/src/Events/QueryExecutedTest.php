<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\RawStatement;
use Tests\Kirameki\Database\DatabaseTestCase;

class QueryExecutedTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $result = new QueryResult(new RawStatement('SELECT 1'), 'SELECT 1', [], 0.0, 0, []);
        $event = new QueryExecuted($connection, $result);
        $this->assertSame($connection, $event->connection);
        $this->assertSame($result, $event->result);
    }
}
