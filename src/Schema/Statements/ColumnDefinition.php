<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
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
     * @param ForeignKeyConstraint|null $references
     */
    public function __construct(
        public readonly string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
        public ?bool $nullable = null,
        public mixed $default = null,
        public ?ForeignKeyConstraint $references = null,
    )
    {
    }
}
