<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Execution;

/**
 * @template TStatement of SchemaStatement
 * @extends Execution<TStatement>
 */
class SchemaExecution extends Execution
{
    /**
     * @param TStatement $statement
     * @param float $elapsedMs
     */
    public function __construct(
        SchemaStatement $statement,
        float $elapsedMs,
    )
    {
        parent::__construct($statement, $elapsedMs);
    }
}
