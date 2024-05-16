<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Support\IsolationLevel;

class TransactionBegan extends DatabaseEvent
{
    /**
     * @param Connection $connection
     * @param IsolationLevel|null $isolationLevel
     */
    public function __construct(
        Connection $connection,
        public readonly ?IsolationLevel $isolationLevel,
    )
    {
        parent::__construct($connection);
    }
}
