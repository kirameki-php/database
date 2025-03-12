<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum Operator: string
{
    case Equals = '=';
    case NotEquals = '!=';
    case LessThan = '<';
    case LessThanOrEqualTo = '<=';
    case GreaterThan = '>';
    case GreaterThanOrEqualTo = '>=';
    case In = 'IN';
    case NotIn = 'NOT IN';
    case Between = 'BETWEEN';
    case NotBetween = 'NOT BETWEEN';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
    case InRange = '_RANGE_';
    case NotInRange = '_NOT_RANGE_';
}
