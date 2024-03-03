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
    protected ?int $affectedRowsCountLazy = null;

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
        if ($this->affectedRowsCountLazy === null) {
            $rowCount = $this->execution->affectedRowCount;
            $this->affectedRowsCountLazy = ($rowCount instanceof Closure)
                ? $rowCount()
                : $rowCount;
        }
        return $this->affectedRowsCountLazy;
    }

    /**
     * @return float
     */
    public function getExecTimeInMilliSeconds(): float
    {
        return $this->execution->execTimeMs;
    }

    /**
     * @return float|null
     */
    public function getFetchTimeInMilliSeconds(): ?float
    {
        return $this->execution->fetchTimeMs;
    }

    /**
     * @return float
     */
    public function getTotalTimeInMilliSeconds(): float
    {
        $execTime = $this->getExecTimeInMilliSeconds();
        $fetchTime = $this->getFetchTimeInMilliSeconds() ?? 0.0;
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
