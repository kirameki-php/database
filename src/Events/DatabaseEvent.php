<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\DatabaseConnection;
use Kirameki\Event\Event;

abstract class DatabaseEvent extends Event
{
    /**
     * @param DatabaseConnection $connection
     */
    public function __construct(
        public readonly DatabaseConnection $connection,
    )
    {
    }
}
