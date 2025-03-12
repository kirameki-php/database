<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum SortOrder: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';

    /**
     * @return self
     */
    public function reverse(): self
    {
        return match ($this) {
            self::Ascending => self::Descending,
            self::Descending => self::Ascending,
        };
    }
}
