<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Statements\Query\QueryResult;
use Kirameki\Database\Statements\Query\QueryStatement;

class QueryExecuted extends StatementExecuted
{
    /**
     * @template TStatement of QueryStatement
     * @param QueryResult<TStatement> $result
     */
    public function __construct(
        public readonly QueryResult $result,
    )
    {
        parent::__construct($result->connection, $result->execution->statement);
    }
}
