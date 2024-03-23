<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Collections\Vec;

/**
 * @template TStatement of QueryStatement
 * @extends Vec<mixed>
 */
class QueryResult extends Vec
{
    /**
     * @param QueryExecution<TStatement> $info
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
