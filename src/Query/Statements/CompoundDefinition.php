<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\CompoundOperator;
use Kirameki\Database\Query\Support\Ordering;

class CompoundDefinition
{
    /**
     * @param CompoundOperator $operator
     * @param SelectStatement $query
     * @param array<string, Ordering>|null $orderBy
     * @param int|null $limit
     */
    public function __construct(
        public readonly CompoundOperator $operator,
        public readonly SelectStatement $query,
        public ?array $orderBy = null,
        public ?int $limit = null,
    )
    {
    }
}
