<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Kirameki\Database\Connection;
use Kirameki\Support\Sequence;

/**
 * @extends Sequence<int, mixed>
 */
class Result extends Sequence
{
    use HasExecutionInfo;

    /**
     * @param Connection $connection
     * @param Execution $execution
     */
    public function __construct(Connection $connection, Execution $execution)
    {
        parent::__construct($execution->rowIterator);
        $this->setExecutionInfo($connection, $execution);
    }
}
