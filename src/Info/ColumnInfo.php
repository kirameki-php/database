<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Database\Info\Statements\ColumnType;

readonly class ColumnInfo
{
    /**
     * @param string $name
     * @param ColumnType $type
     * @param bool $nullable
     * @param int $position
     */
    public function __construct(
        public string $name,
        public ColumnType $type,
        public bool $nullable,
        public int $position,
    )
    {
    }
}
