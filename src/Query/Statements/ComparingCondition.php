<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Expression;

class ComparingCondition extends Condition
{
    /**
     * @param string|Tuple|Expression $column
     * @param Operator $operator
     * @param mixed $value
     * @param Logic|null $link
     * @param Condition|null $next
     */
    public function __construct(
        public string|Tuple|Expression $column,
        public Operator $operator,
        mixed $value,
        ?Logic $link = null,
        ?Condition $next = null,
    )
    {
        parent::__construct($value, $link, $next);
    }
}
