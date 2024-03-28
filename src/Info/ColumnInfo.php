<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

readonly class ColumnInfo
{
    /**
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param int $position
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
        public int $position,
    )
    {
    }
}
