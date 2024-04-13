<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Collections\Vec;

/**
 * @template TQueryStatement of QueryStatement
 * @extends Vec<mixed>
 */
class QueryResult extends Vec
{
    /**
     * @param QueryExecution<TQueryStatement> $info
     * @param iterable<int, mixed> $rows
     */
    public function __construct(
        public readonly QueryExecution $info,
        iterable $rows,
    )
    {
        parent::__construct($rows);
    }
}
