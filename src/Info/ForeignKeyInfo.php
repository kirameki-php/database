<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

readonly class ForeignKeyInfo
{
    /**
     * @param string $name
     * @param list<string> $columns
     * @param string $referencedTable
     * @param list<string> $referencedColumns
     */
    public function __construct(
        public string $name,
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
    )
    {
    }
}
