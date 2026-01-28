<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\DatabaseConnection;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @template TQueryStatement of QueryStatement
     * @template TRow of mixed
     * @param QueryResult<TQueryStatement, TRow> $result
     */
    public function __construct(
        DatabaseConnection $connection,
        public readonly QueryResult $result,
    )
    {
        parent::__construct($connection);
    }
}
