<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Throwable;

class TransactionRolledBack extends DatabaseEvent
{
    /**
     * @var Throwable
     */
    public readonly Throwable $throwable;

    /**
     * @param Connection $connection
     * @param Throwable $throwable
     */
    public function __construct(Connection $connection, Throwable $throwable)
    {
        parent::__construct($connection);
        $this->throwable = $throwable;
    }
}
