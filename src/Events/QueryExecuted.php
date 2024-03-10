<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Result;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @param Connection $connection
     * @param Result $result
     */
    public function __construct(
        Connection $connection,
        public readonly Result $result,
    )
    {
        parent::__construct($connection);
    }
}
