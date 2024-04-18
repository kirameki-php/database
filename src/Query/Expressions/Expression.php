<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;

abstract class Expression
{
    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    abstract public function generateTemplate(QuerySyntax $syntax): string;

    /**
     * @return array<int, mixed>
     */
    public function generateParameters(QuerySyntax $syntax): array
    {
        return [];
    }
}
