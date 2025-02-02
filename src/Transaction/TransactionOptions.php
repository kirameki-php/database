<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

class TransactionOptions
{
    /**
     * @param IsolationLevel|null $isolationLevel
     */
    public function __construct(
        public readonly ?IsolationLevel $isolationLevel = null,
    )
    {
    }
}
