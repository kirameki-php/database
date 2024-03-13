<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Query\QueryResult;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @param Connection $connection
     * @param QueryResult $result
     */
    public function __construct(
        Connection $connection,
        public readonly QueryResult $result,
    )
    {
        parent::__construct($connection);
    }
}
