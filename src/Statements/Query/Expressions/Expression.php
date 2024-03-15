<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;

abstract class Expression
{
    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    abstract public function prepare(QuerySyntax $syntax): string;

    /**
     * @return array<int, mixed>
     */
    public function getParameters(): array
    {
        return [];
    }
}
