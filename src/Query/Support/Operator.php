<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

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
    case Exists = 'EXISTS';
    case NotExists = 'NOT EXISTS';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
    case Raw = '_RAW_';
    case Range = '_RANGE_';
}
