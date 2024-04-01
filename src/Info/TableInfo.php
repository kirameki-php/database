<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;

readonly class TableInfo
{
    /**
     * @param string $table
     * @param Map<string, ColumnInfo> $columns
     * @param Vec<IndexInfo> $indexes
     */
    public function __construct(
        public string $table,
        public Map $columns,
        public Vec $indexes,
    )
    {
    }
}
