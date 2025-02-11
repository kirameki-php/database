<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

abstract class ConditionsStatement extends QueryStatement
{
    /**
     * @var list<WithDefinition>|null
     */
    public ?array $with = null;

    /**
     * @var array<ConditionDefinition>|null
     */
    public ?array $where = null;

    /**
     * @var array<string, Ordering>|null
     */
    public ?array $orderBy = null;

    /**
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * @return void
     */
    public function __clone(): void
    {
        if ($this->where !== null) {
            $where = [];
            foreach ($this->where as $condition) {
                $where[] = clone $condition;
            }
            $this->where = $where;
        }
    }
}
