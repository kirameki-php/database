<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\DatabaseConnection;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;

class SchemaExecuted extends DatabaseEvent
{
    /**
     * @template TSchemaStatement of SchemaStatement
     * @param DatabaseConnection $connection
     * @param SchemaResult<TSchemaStatement> $result
     */
    public function __construct(
        DatabaseConnection $connection,
        public readonly SchemaResult $result,
    )
    {
        parent::__construct($connection);
    }
}
