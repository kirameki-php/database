<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Result;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var Result
     */
    public readonly Result $result;

    /**
     * @param Connection $connection
     * @param Result $result
     */
    public function __construct(Connection $connection, Result $result)
    {
        parent::__construct($connection);
        $this->result = $result;
    }
}
