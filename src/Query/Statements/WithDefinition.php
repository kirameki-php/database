<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class WithDefinition
{
    public QueryStatement $statement;

    /**
     * @param string $name
     * @param bool $recursive
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $recursive,
    )
    {
    }
}
