<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Kirameki\Database\Connection;

interface TransactionInfo
{
    /**
     * @var Connection
     */
    public Connection $connection { get; }

    /**
     * @var ?IsolationLevel
     */
    public ?IsolationLevel $isolationLevel { get; }
}
