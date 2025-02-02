<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Transaction\IsolationLevel;
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Database\Transaction\TransactionOptions;
use Tests\Kirameki\Database\DatabaseTestCase;

class TransactionBeganTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $txInfo = new TransactionContext($connection, new TransactionOptions(IsolationLevel::Serializable));
        $event = new TransactionBegan($txInfo);
        $this->assertSame($connection, $event->connection);
        $this->assertSame($txInfo, $event->info);
    }
}
