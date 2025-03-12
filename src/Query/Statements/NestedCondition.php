<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class NestedCondition extends Condition
{
    /**
     * @param Condition $value
     * @param Logic|null $link
     * @param Condition|null $next
     */
    public function __construct(
        Condition $value,
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
