<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Savepoint;

class TransactionSaved extends DatabaseEvent
{
    /**
     * @var Savepoint
     */
    public readonly Savepoint $savepoint;

    /**
     * @param Connection $connection
     * @param Savepoint $savepoint
     */
    public function __construct(Connection $connection, Savepoint $savepoint)
    {
        parent::__construct($connection);
        $this->savepoint = $savepoint;
    }
}
