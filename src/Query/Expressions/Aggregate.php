<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

class Aggregate extends Expr
{
    /**
     * @param string $function
     * @param string $column
     * @param string|null $as
     */
    public function __construct(
        public readonly string $function,
        public readonly string $column,
        public readonly ?string $as = null,
    )
    {
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function prepare(Formatter $formatter): string
    {
        $expr = $this->function;
        $expr.= '(';
        $expr.= $formatter->columnize($this->column);
        $expr.= ')';
        if ($this->as !== null) {
            $expr.= ' AS ' . $formatter->quote($this->as);
        }
        return $expr;
    }
}
