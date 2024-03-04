<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

abstract class BaseStatement
{
    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }
}
