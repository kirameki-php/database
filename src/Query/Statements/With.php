<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class With
{
    /**
     * @param string $name
     * @param bool $recursive
     * @param QueryStatement $as
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $recursive,
        public readonly QueryStatement $as,
    )
    {
    }
}
