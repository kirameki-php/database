<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class Compound
{
    /**
     * @param CompoundType $operator
     * @param SelectStatement $query
     * @param array<string, Ordering>|null $orderBy
     * @param int|null $limit
     */
    public function __construct(
        public readonly CompoundType $operator,
        public readonly SelectStatement $query,
        public ?array $orderBy = null,
        public ?int $limit = null,
    )
    {
    }
}
