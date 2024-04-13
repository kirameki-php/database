<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class ConnectionEstablished extends DatabaseEvent
{
    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection,
    )
    {
        parent::__construct($connection);
    }
}
