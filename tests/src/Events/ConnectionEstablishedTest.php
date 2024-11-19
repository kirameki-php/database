<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Events;

use Kirameki\Database\Events\ConnectionEstablished;
use Tests\Kirameki\Database\DatabaseTestCase;

class ConnectionEstablishedTest extends DatabaseTestCase
{
    public function test_initialization(): void
    {
        $connection = $this->sqliteConnection();
        $event = new ConnectionEstablished($connection);
        $this->assertSame($connection, $event->connection);
    }
}
