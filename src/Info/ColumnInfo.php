<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

readonly class ColumnInfo
{
    /**
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param mixed|null $default
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
        public mixed $default = null,
    )
    {
    }
}
