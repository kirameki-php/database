<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Statement;

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
        Statement $statement,
        float $elapsedMs,
    )
    {
        parent::__construct($statement, $elapsedMs);
    }
}
