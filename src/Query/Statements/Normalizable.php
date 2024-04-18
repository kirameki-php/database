<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Iterator;
use Kirameki\Database\Query\Syntax\QuerySyntax;

interface Normalizable
{
    /**
     * @param QuerySyntax $syntax
     * @param iterable<int, mixed> $rows
     * @return Iterator<int, mixed>
     */
    public function normalize(QuerySyntax $syntax, iterable $rows): Iterator;
}
