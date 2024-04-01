<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
    public ?bool $primaryKey = null;

    public ?bool $nullable = false;

    public ?bool $autoIncrement = null;

    public mixed $default = null;

    /**
     * @param string $name
     * @param string|null $type
     * @param int|null $size
     * @param int|null $scale
     */
    public function __construct(
        public readonly string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
    )
    {
    }
}
