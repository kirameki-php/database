<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

readonly class IndexInfo
{
    /**
     * @param string $name
     * @param list<string> $columns
     * @param string $type
     */
    public function __construct(
        public string $name,
        public array $columns,
        public string $type,
    )
    {
    }
}
