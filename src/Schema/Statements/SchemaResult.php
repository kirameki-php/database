<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

/**
 * @template TSchemaStatement of SchemaStatement
 */
readonly class SchemaResult
{
    /**
     * @param TSchemaStatement $statement
     * @param list<string> $commands
     * @param float $elapsedMs
     * @param bool $dryRun
     */
    public function __construct(
        public SchemaStatement $statement,
        public array $commands,
        public float $elapsedMs,
        public bool $dryRun,
    )
    {
    }
}
