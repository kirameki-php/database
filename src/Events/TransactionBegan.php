<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Transaction;

class TransactionBegan extends DatabaseEvent
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
