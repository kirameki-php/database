<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Event\Event;

abstract class DatabaseEvent extends Event
{
    /**
     * @var Connection
     */
    public readonly Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}
