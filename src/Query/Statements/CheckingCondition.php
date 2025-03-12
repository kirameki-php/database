<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class CheckingCondition extends Condition
{
    /**
     * @param QueryStatement $value
     * @param bool $negated
     * @param Logic|null $link
     * @param Condition|null $next
     */
    public function __construct(
        QueryStatement $value,
        public readonly bool $negated = false,
        ?Logic $link = null,
        ?Condition $next = null,
    )
    {
        parent::__construct($value, $link, $next);
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        parent::__clone();
        $this->value = clone $this->value;
    }
}
