<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Schema\SchemaStatement;

class SchemaExecuted extends StatementExecuted
{
    /**
     * @template TStatement of SchemaStatement
     * @param Connection $connection
     * @param Execution<TStatement> $execution
     */
    public function __construct(
        Connection $connection,
        public readonly Execution $execution,
    )
    {
        parent::__construct($connection, $execution->statement);
    }
}
