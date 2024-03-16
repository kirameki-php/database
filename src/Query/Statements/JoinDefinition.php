<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\JoinType;

class JoinDefinition
{
    /**
     * @var ConditionDefinition
     */
    public ConditionDefinition $condition;

    /**
     * @param JoinType $type
     * @param string $table
     */
    public function __construct(
        public readonly JoinType $type,
        public readonly string $table,
    )
    {
    }
}
