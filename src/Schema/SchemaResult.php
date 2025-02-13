<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Schema\Statements\SchemaStatement;

/**
 * @template TSchemaStatement of SchemaStatement
 */
class SchemaResult
{
    /**
     * @param TSchemaStatement $statement
     * @param list<string> $commands
     * @param float $elapsedMs
     */
    public function __construct(
        public readonly SchemaStatement $statement,
        public readonly array $commands,
        public readonly float $elapsedMs,
    )
    {
    }
}
