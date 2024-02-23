<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Adapters\Adapter;

class Execution
{
    /**
     * @param Adapter $adapter
     * @param string $statement
     * @param array<mixed> $bindings
     * @param iterable<int, mixed> $rowIterator
     * @param Closure(): int $affectedRowCount
     * @param float $execTimeMs
     * @param ?float $fetchTimeMs
     */
    public function __construct(
        public readonly Adapter $adapter,
        public readonly string $statement,
        public readonly array $bindings,
        public readonly iterable $rowIterator,
        public readonly int|Closure $affectedRowCount,
        public readonly float $execTimeMs,
        public readonly ?float $fetchTimeMs,
    )
    {
    }
}
