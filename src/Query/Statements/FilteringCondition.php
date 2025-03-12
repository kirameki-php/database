<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Expression;

class FilteringCondition extends Condition
{
    /**
     * @param string|iterable<int, string>|Expression $column
     * @param Operator $operator
     * @param mixed $value
     * @param Logic|null $link
     * @param Condition|null $next
     */
    public function __construct(
        public string|iterable|Expression $column,
        public Operator $operator,
        mixed $value,
        ?Logic $link = null,
        ?Condition $next = null,
    )
    {
        parent::__construct($value, $link, $next);
    }
}
