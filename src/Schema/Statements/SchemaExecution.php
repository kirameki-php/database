<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

/**
 * @template TSchemaStatement of SchemaStatement
 */
readonly class SchemaExecution
{
    /**
     * @param TSchemaStatement $statement
     * @param float $elapsedMs
     */
    public function __construct(
        public SchemaStatement $statement,
        public float $elapsedMs,
    )
    {
    }
}
