<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class JoinDefinition
{
    /**
     * @param JoinType $type
     * @param string $table
     * @param Condition|null $condition
     * @param list<string>|null $using
     */
    public function __construct(
        public readonly JoinType $type,
        public readonly string $table,
        public ?Condition $condition = null,
        public ?array $using = null,
    )
    {
    }
}
