<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum NullOrder: string
{
    case First = 'NULLS FIRST';
    case Last = 'NULLS LAST';
}
