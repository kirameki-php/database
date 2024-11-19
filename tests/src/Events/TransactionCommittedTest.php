<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\TransactionCommitted;
use Tests\Kirameki\Database\DatabaseTestCase;

class TransactionCommittedTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $event = new TransactionCommitted($connection);
        $this->assertSame($connection, $event->connection);
    }
}
