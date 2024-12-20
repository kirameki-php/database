<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements\Select;

enum CompoundOperator: string
{
    case Union = 'UNION';
    case UnionAll = 'UNION ALL';
    case Intersect = 'INTERSECT';
    case Except = 'EXCEPT';
}
