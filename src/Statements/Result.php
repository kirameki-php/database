<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;

/**
 * @extends Vec<mixed>
 */
class Result extends Vec
{
    /**
     * @param Connection $connection
     * @param Execution $execution
     */
    public function __construct(
        public readonly Connection $connection,
        public readonly Execution $execution,
    )
    {
        parent::__construct($execution->rowIterator);
    }

    /**
     * @return string
     */
    public function getExecutedQuery(): string
    {
        $syntax = $this->connection->getAdapter()->getQuerySyntax();
        return $syntax->interpolate($this->execution->statement);
    }
}
