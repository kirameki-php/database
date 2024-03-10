<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class ColumnDefinition
{
    public ?bool $primaryKey = null;

    public ?bool $nullable = true;

    public ?bool $autoIncrement = null;

    public ?string $comment = null;

    public mixed $default = null;

    /**
     * @param string $name
     * @param string|null $type
     * @param int|null $size
     * @param int|null $scale
     */
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
    )
    {
    }
}
