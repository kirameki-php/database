<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Connection;

trait HasExecutionInfo
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var Execution
     */
    protected Execution $execution;

    /**
     * @var int|null
     */
    protected ?int $affectedRowsCountLazy = null;

    /**
     * @param Connection $connection
     * @param Execution $execution
     */
    protected function setExecutionInfo(Connection $connection, Execution $execution): void
    {
        $this->connection = $connection;
        $this->execution = $execution;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->execution->statement;
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->execution->bindings;
    }

    /**
     * @return int
     */
    public function getAffectedRowCount(): int
    {
        if ($this->affectedRowsCountLazy === null) {
            $rowCount = $this->execution->affectedRowCount;
            $this->affectedRowsCountLazy = ($rowCount instanceof Closure) ? $rowCount() : $rowCount;
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
        $formatter = $this->getConnection()->getQueryFormatter();
        $statement = $this->getStatement();
        $bindings = $this->getBindings();
        return $formatter->interpolate($statement, $bindings);
    }
}
