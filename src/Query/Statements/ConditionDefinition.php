<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Expression;

class ConditionDefinition
{
    /**
     * @var Operator
     */
    public Operator $operator;

    /**
     * @var bool
     */
    public bool $negated = false;

    /**
     * @var mixed
     */
    public mixed $value = null;

    /**
     * @var LogicType|null
     */
    public ?LogicType $nextLogic = null;

    /**
     * @var static|null
     */
    public ?self $next = null;

    /**
     * @param string|iterable<int, string>|Expression|null $column
     */
    public function __construct(
        public string|iterable|Expression|null $column = null,
    )
    {
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }
    }
}
