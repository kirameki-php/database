<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum Operator: string
{
    case Equals = '=';
    case LessThan = '<';
    case LessThanOrEqualTo = '<=';
    case GreaterThan = '>';
    case GreaterThanOrEqualTo = '>=';
    case In = 'IN';
    case Between = 'BETWEEN';
    case Exists = 'EXISTS';
    case Like = 'LIKE';
    case Raw = '_RAW_';
    case Range = '_RANGE_';
}
