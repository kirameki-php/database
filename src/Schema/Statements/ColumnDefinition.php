<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
    public const int DEFAULT_INT_SIZE = 8;
    public const int DEFAULT_STRING_SIZE = 65535;
    public const int DEFAULT_TIME_PRECISION = 6;

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
     * @param mixed|null $default
     */
    public function __construct(
        public readonly string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
        public ?bool $nullable = null,
        public mixed $default = null,
    )
    {
    }
}
