<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Collections\Vec;

/**
 * @template TQueryStatement of QueryStatement
 * @extends Vec<mixed>
 */
class QueryResult extends Vec
{
    /**
     * @param QueryExecutable<TQueryStatement> $executable
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     * @param iterable<int, mixed> $rows
     */
    public function __construct(
        public readonly QueryExecutable $executable,
        public readonly float $elapsedMs,
        protected int|Closure $affectedRowCount,
        iterable $rows,
    )
    {
        parent::__construct($rows);
    }

    /**
     * @return int
     */
    public function getAffectedRowCount(): int
    {
        if ($this->affectedRowCount instanceof Closure) {
            $this->affectedRowCount = ($this->affectedRowCount)();
        }
        return $this->affectedRowCount;
    }
}
