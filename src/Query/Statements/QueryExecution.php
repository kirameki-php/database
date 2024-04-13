<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;

/**
 * @template TQueryStatement of QueryStatement
 */
class QueryExecution
{
    /**
     * @param QueryExecutable<TQueryStatement> $executable
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     */
    public function __construct(
        public readonly QueryExecutable $executable,
        public readonly float $elapsedMs,
        protected int|Closure $affectedRowCount,
    )
    {
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
