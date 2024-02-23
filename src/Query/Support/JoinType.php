<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum JoinType: string
{
    case Inner = 'JOIN';
    case Cross = 'CROSS JOIN';
    case Left = 'LEFT JOIN';
    case Right = 'RIGHT JOIN';
    case Full = 'FULL JOIN';
}
