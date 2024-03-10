<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Execution;

class SchemaExecuted extends DatabaseEvent
{
    /**
     * @param Connection $connection
     * @param Execution $execution
     */
    public function __construct(
        Connection $connection,
        public Execution $execution,
    )
    {
        parent::__construct($connection);
    }
}
