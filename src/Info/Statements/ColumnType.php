<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

enum ColumnType: string
{
    case Int = 'int';
    case String = 'string';
    case Decimal = 'decimal';
    case Float = 'float';
    case Bool = 'bool';
    case Timestamp = 'timestamp';
    case Json = 'json';
    case Blob = 'blob';
}
