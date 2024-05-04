<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

readonly class ForeignKeyInfo
{
    /**
     * @param string|null $name
     * @param list<string> $foreignKeyColumns
     * @param string $referenceTable
     * @param list<string> $referenceColumns
     */
    public function __construct(
        public array $foreignKeyColumns,
        public string $referenceTable,
        public array $referenceColumns,
        public ?string $name,
    )
    {
    }
}
