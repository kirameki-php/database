<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;

interface Expression
{
    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    public function toValue(QuerySyntax $syntax): string;
}
