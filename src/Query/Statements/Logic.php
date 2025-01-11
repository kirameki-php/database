<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum Logic: string
{
    case And = 'AND';
    case Or = 'OR';
    case Not = 'NOT';
}
