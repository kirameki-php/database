<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;

class SchemaExecuted extends StatementExecuted
{
    /**
     * @template TSchemaStatement of SchemaStatement
     * @param Connection $connection
     * @param SchemaResult<TSchemaStatement> $execution
     */
    public function __construct(
        Connection $connection,
        public readonly SchemaResult $execution,
    )
    {
        parent::__construct($connection, $execution->statement);
    }
}
