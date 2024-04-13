<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\SchemaExecution;
use Kirameki\Database\Schema\Statements\SchemaStatement;

class SchemaExecuted extends StatementExecuted
{
    /**
     * @template TSchemaStatement of SchemaStatement
     * @param Connection $connection
     * @param SchemaExecution<TSchemaStatement> $execution
     */
    public function __construct(
        Connection $connection,
        public readonly SchemaExecution $execution,
    )
    {
        parent::__construct($connection, $execution->statement);
    }
}
