<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Expressions\Column;
use Kirameki\Database\Statements\Query\Support\Operator;

class ConditionDefinition
{
    /**
     * @var Operator|null
     */
    public ?Operator $operator = null;

    /**
     * @var bool
     */
    public bool $negated = false;

    /**
     * @var mixed
     */
    public mixed $value = null;

    /**
     * @var string|null
     */
    public ?string $nextLogic = null;

    /**
     * @var static|null
     */
    public ?self $next = null;

    /**
     * @param string|Column|null $column
     */
    public function __construct(
        public string|Column|null $column = null,
    )
    {
    }

    public function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }
    }
}