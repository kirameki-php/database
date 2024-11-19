<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\JoinType;

class JoinDefinition
{
    /**
     * @param JoinType $type
     * @param string $table
     * @param ConditionDefinition|null $condition
     * @param list<string>|null $using
     */
    public function __construct(
        public readonly JoinType $type,
        public readonly string $table,
        public ?ConditionDefinition $condition = null,
        public ?array $using = null,
    )
    {
    }
}
