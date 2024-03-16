<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;

class Raw extends Expression
{
    /**
     * @param string $value
     */
    public function __construct(
        public readonly string $value,
    )
    {
    }

    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    public function prepare(QuerySyntax $syntax): string
    {
        return $this->value;
    }
}
