<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;

class QueryExecuted extends StatementExecuted
{
    /**
     * @template TStatement of QueryStatement
     * @param QueryResult<TStatement> $result
     */
    public function __construct(
        Connection $connection,
        public readonly QueryResult $result,
    )
    {
        parent::__construct($connection, $result->info->statement);
    }
}
