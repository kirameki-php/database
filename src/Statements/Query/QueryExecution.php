<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Closure;
use Kirameki\Database\Statements\Execution;

/**
 * @template TStatement of QueryStatement
 * @extends Execution<TStatement>
 */
class QueryExecution extends Execution
{
    /**
     * @param TStatement $statement
     * @param iterable<int, mixed> $rowIterator
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     */
    public function __construct(
        QueryStatement $statement,
        float $elapsedMs,
        public readonly iterable $rowIterator,
        protected int|Closure $affectedRowCount,
    )
    {
        parent::__construct($statement, $elapsedMs);
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
