<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statement;

class StatementExecuted extends DatabaseEvent
{
    /**
     * @template TStatement of Statement
     * @param Connection $connection
     * @param TStatement $statement
     */
    public function __construct(
        Connection $connection,
        public readonly Statement $statement,
    )
    {
        parent::__construct($connection);
    }
}
