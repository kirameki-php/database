<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Transaction;

class TransactionBegan extends DatabaseEvent
{
    /**
     * @var Transaction
     */
    public readonly Transaction $transaction;

    /**
     * @param Connection $connection
     * @param Transaction $transaction
     */
    public function __construct(Connection $connection, Transaction $transaction)
    {
        parent::__construct($connection);
        $this->transaction = $transaction;
    }
}
