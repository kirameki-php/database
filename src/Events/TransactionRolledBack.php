<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Throwable;

class TransactionRolledBack extends DatabaseEvent
{
    /**
     * @param Connection $connection
     * @param Throwable $cause
     */
    public function __construct(
        Connection $connection,
        public readonly Throwable $cause,
    )
    {
        parent::__construct($connection);
    }
}
