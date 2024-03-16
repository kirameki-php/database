<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;

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
     * @param QuerySyntax $syntax
     * @return string
     */
    public function prepare(QuerySyntax $syntax): string
    {
        $expr = $this->function;
        $expr.= '(';
        $expr.= $syntax->asColumn($this->column);
        $expr.= ')';
        if ($this->as !== null) {
            $expr.= ' AS ' . $syntax->asIdentifier($this->as);
        }
        return $expr;
    }
}
