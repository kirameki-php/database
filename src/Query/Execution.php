<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Adapters\DatabaseConfig;

class Execution
{
    /**
     * @param DatabaseConfig $config
     * @param string $statement
     * @param iterable<array-key, mixed> $bindings
     * @param iterable<int, mixed> $rowIterator
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     */
    public function __construct(
        public readonly DatabaseConfig $config,
        public readonly string $statement,
        public readonly iterable $bindings,
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
