<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

interface StatementBuilder
{
    /**
     * @return Result
     */
    public function execute(): Result;
}
