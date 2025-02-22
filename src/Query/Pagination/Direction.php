<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

enum Direction
{
    case Next;
    case Previous;

    public function isNext(): bool
    {
        return $this === Direction::Next;
    }
}
