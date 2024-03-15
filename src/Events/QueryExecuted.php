<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Query\QueryResult;
use Kirameki\Database\Statements\Query\QueryStatement;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @template TStatement of QueryStatement
     * @param Connection $connection
     * @param QueryResult<TStatement> $result
     */
    public function __construct(
        Connection $connection,
        public readonly QueryResult $result,
    )
    {
        parent::__construct($connection);
    }
}
