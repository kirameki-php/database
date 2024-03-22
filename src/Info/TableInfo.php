<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Map;

readonly class TableInfo
{
    /**
     * @param string $table
     * @param Map<string, ColumnInfo> $columns
     */
    public function __construct(
        public string $table,
        public Map $columns,
    )
    {
    }
}
