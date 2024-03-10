<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

use Closure;
use Kirameki\Database\Adapters\DatabaseConfig;

class Execution
{
    /**
     * @param DatabaseConfig $config
     * @param Statement $statement
     * @param iterable<int, mixed> $rowIterator
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     */
    public function __construct(
        public readonly DatabaseConfig $config,
        public readonly Statement $statement,
        public readonly iterable $rowIterator,
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
