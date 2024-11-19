<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Tests\Kirameki\Database\DatabaseTestCase;

class TransactionBeganTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $event = new TransactionBegan($connection, IsolationLevel::Serializable);
        $this->assertSame($connection, $event->connection);
        $this->assertSame(IsolationLevel::Serializable, $event->isolationLevel);
    }
}
