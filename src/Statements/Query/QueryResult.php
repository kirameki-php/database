<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;

/**
 * @template TStatement of QueryStatement
 * @extends Vec<mixed>
 */
class QueryResult extends Vec
{
    /**
     * @param Connection $connection
     * @param QueryExecution<TStatement> $execution
     */
    public function __construct(
        public readonly Connection $connection,
        public readonly QueryExecution $execution,
    )
    {
        parent::__construct($execution->rowIterator);
    }
}
