<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\SchemaStatement;

class StatementExecuted extends DatabaseEvent
{
    /**
     * @template TSchemaStatement of SchemaStatement
     * @param Connection $connection
     * @param TSchemaStatement $statement
     */
    public function __construct(
        Connection $connection,
        public readonly SchemaStatement $statement,
    )
    {
        parent::__construct($connection);
    }
}
