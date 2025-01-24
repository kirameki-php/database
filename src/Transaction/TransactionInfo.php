<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Kirameki\Database\Connection;

interface TransactionInfo
{
    /**
     * @var Connection
     */
    public protected(set) Connection $connection { get; set; }

    /**
     * @var ?IsolationLevel
     */
    public protected(set) ?IsolationLevel $isolationLevel { get; set; }
}
