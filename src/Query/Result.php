<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;

/**
 * @extends Vec<mixed>
 */
class Result extends Vec
{
    /**
     * @var int|null
     */
    protected ?int $resolvedAffectedRowsCount = null;

    /**
     * @param Connection $connection
     * @param Execution $execution
     */
    public function __construct(
        public readonly Connection $connection,
        public readonly Execution $execution,
    )
    {
        parent::__construct($execution->rowIterator);
    }

    /**
     * @return int
     */
    public function getAffectedRowCount(): int
    {
        if ($this->resolvedAffectedRowsCount === null) {
            $rowCount = $this->execution->affectedRowCount;
            $this->resolvedAffectedRowsCount = ($rowCount instanceof Closure)
                ? $rowCount()
                : $rowCount;
        }
        return $this->resolvedAffectedRowsCount;
    }

    /**
     * @return float
     */
    public function getTotalTimeInMilliSeconds(): float
    {
        $execution = $this->execution;

        $execTime = $execution->execTimeMs;
        $fetchTime = $execution->fetchTimeMs ?? 0.0;

        return $execTime + $fetchTime;
    }

    /**
     * @return string
     */
    public function getExecutedQuery(): string
    {
        $formatter = $this->connection->getQueryFormatter();
        return $formatter->interpolate(
            $this->execution->statement,
            $this->execution->bindings,
        );
    }
}
