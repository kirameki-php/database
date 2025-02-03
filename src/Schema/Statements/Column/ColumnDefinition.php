<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyConstraint;

class ColumnDefinition
{
    /**
     * @param string $name
     * @param string|null $type
     * @param int|null $size
     * @param int|null $scale
     * @param bool|null $nullable
     * @param mixed|null $default
     * @param ForeignKeyConstraint|null $references
     * @param bool|null $primaryKey
     * @param int|bool|null $autoIncrement
     */
    public function __construct(
        public readonly string $name,
        public ?string $type = null,
        public ?int $size = null,
        public ?int $scale = null,
        public ?bool $nullable = null,
        public mixed $default = null,
        public ?ForeignKeyConstraint $references = null,
        public ?bool $primaryKey = null,
        public int|bool|null $autoIncrement = null,
    )
    {
    }
}
