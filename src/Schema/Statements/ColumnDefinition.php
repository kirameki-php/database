<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
    /**
     * @var mixed
     */
    public mixed $default = null;

    /**
     * @var bool|null
     */
    public ?bool $primaryKey = null;

    /**
     * @var int|null
     */
    public ?int $autoIncrement = null;

    /**
     * @param string $name
     * @param string|null $type
     * @param int|null $size
     * @param int|null $scale
     * @param bool|null $nullable
     */
    public function __construct(
        public readonly string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
        public ?bool $nullable = null,
    )
    {
    }
}
