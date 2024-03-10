<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Formatters\QueryFormatter;

class Aggregate extends Expression
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
     * @param QueryFormatter $formatter
     * @return string
     */
    public function prepare(QueryFormatter $formatter): string
    {
        $expr = $this->function;
        $expr.= '(';
        $expr.= $formatter->asColumn($this->column);
        $expr.= ')';
        if ($this->as !== null) {
            $expr.= ' AS ' . $formatter->asIdentifier($this->as);
        }
        return $expr;
    }
}
