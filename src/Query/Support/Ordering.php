<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

readonly class Ordering
{
    /**
     * @param SortOrder $sort
     * @param NullOrder|null $nulls
     */
    public function __construct(
        public SortOrder $sort,
        public ?NullOrder $nulls = null,
    )
    {
    }
}
