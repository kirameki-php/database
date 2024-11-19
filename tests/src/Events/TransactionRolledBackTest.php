<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\TransactionRolledBack;
use LogicException;
use Tests\Kirameki\Database\DatabaseTestCase;

class TransactionRolledBackTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $exception = new LogicException('Rollback failed');
        $event = new TransactionRolledBack($connection, $exception);
        $this->assertSame($connection, $event->connection);
        $this->assertSame($exception, $event->cause);
    }
}
