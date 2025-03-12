<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

abstract class ConditionStatement extends QueryStatement
{
    /**
     * @var Condition|null
     */
    public ?Condition $where = null;

    /**
     * @return void
     */
    public function __clone(): void
    {
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
    }
}
