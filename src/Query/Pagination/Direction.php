<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

enum Direction: string
{
    case Next = 'next';
    case Previous = 'previous';

    public function isNext(): bool
    {
        return $this === Direction::Next;
    }
}
