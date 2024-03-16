<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;

class Column extends Expression
{
    /**
     * @param string $column
     */
    public function __construct(
        public readonly string $column,
    )
    {
    }

    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    public function prepare(QuerySyntax $syntax): string
    {
        return $syntax->asColumn($this->column);
    }
}
