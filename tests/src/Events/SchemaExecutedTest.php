<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\RawStatement;
use Tests\Kirameki\Database\DatabaseTestCase;

class SchemaExecutedTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $result = new SchemaResult(new RawStatement('SELECT 1'), ['SELECT 1'], 0.0);
        $event = new SchemaExecuted($connection, $result);
        $this->assertSame($connection, $event->connection);
        $this->assertSame($result, $event->result);
    }
}
