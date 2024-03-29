<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Execution;

/**
 * @template TStatement of QueryStatement
 * @extends Execution<TStatement>
 */
class QueryExecution extends Execution
{
    /**
     * @param TStatement $statement
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     */
    public function __construct(
        QueryStatement $statement,
        float $elapsedMs,
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
