<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Kirameki\Database\DatabaseConnection;

interface TransactionInfo
{
    /**
     * @var DatabaseConnection
     */
    public DatabaseConnection $connection {
        get;
    }

    /**
     * @var TransactionOptions|null
     */
    public ?TransactionOptions $options {
        get;
    }
}
