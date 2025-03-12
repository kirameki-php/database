<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum NullOrder: string
{
    case First = 'NULLS FIRST';
    case Last = 'NULLS LAST';

    /**
     * @return self
     */
    public function reverse(): self
    {
        return match ($this) {
            self::First => self::Last,
            self::Last => self::First,
        };
    }
}
