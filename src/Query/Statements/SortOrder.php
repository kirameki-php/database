<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum SortOrder: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
