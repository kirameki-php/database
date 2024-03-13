<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

/**
 * @template TStatement of Statement
 */
abstract class Execution
{
    /**
     * @param TStatement $statement
     * @param float $elapsedMs
     */
    public function __construct(
        public Statement $statement,
        public float $elapsedMs,
    )
    {
    }
}
