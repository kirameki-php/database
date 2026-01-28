<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\DatabaseConnection;

class ConnectionEstablished extends DatabaseEvent
{
    /**
     * @param DatabaseConnection $connection
     */
    public function __construct(
        DatabaseConnection $connection,
    )
    {
        parent::__construct($connection);
    }
}
