<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class Cte
{
    /**
     * @param string $name
     * @param list<string> $columns
     * @param QueryStatement $as
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly QueryStatement $as,
    )
    {
    }
}
