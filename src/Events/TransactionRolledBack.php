<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\DatabaseConnection;
use Throwable;

class TransactionRolledBack extends TransactionEvent
{
    /**
     * @param DatabaseConnection $connection
     * @param Throwable $cause
     */
    public function __construct(
        DatabaseConnection $connection,
        public readonly Throwable $cause,
    )
    {
        parent::__construct($connection);
    }
}
