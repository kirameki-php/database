<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Transaction\TransactionInfo;

class TransactionCommitting extends TransactionEvent
{
    /**
     * @param TransactionInfo $info
     */
    public function __construct(
        public readonly TransactionInfo $info,
    )
    {
        parent::__construct($info->connection);
    }
}
