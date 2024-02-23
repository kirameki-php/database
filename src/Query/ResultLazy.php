<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Kirameki\Database\Connection;
use Kirameki\Support\SequenceLazy;

/**
 * @extends SequenceLazy<int, mixed>
 */
class ResultLazy extends SequenceLazy
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
