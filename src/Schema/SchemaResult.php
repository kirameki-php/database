<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Schema\Statements\SchemaStatement;

/**
 * @template TSchemaStatement of SchemaStatement
 */
readonly class SchemaResult
{
    /**
     * @param SchemaStatement $statement
     * @param list<string> $commands
     * @param float $elapsedMs
     */
    public function __construct(
        public SchemaStatement $statement,
        public array $commands,
        public float $elapsedMs,
    )
    {
    }
}
