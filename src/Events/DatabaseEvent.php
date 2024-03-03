<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Event\Event;

abstract class DatabaseEvent extends Event
{
    /**
     * @param Connection $connection
     */
    public function __construct(
        public readonly Connection $connection,
    )
    {
    }
}
