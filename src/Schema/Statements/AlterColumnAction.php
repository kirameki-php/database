<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Support\AlterType;

class AlterColumnAction
{
    /**
     * @var string|null
     */
    public ?string $positionType = null;

    /**
     * @var string|null
     */
    public ?string $positionColumn = null;

    /**
     * @param AlterType $type
     * @param ColumnDefinition $definition
     */
    public function __construct(
        public readonly AlterType $type,
        public readonly ColumnDefinition $definition,
    )
    {
    }

    /**
     * @return bool
     */
    public function isAdd(): bool
    {
        return $this->type === AlterType::Add;
    }

    /**
     * @return bool
     */
    public function isModify(): bool
    {
        return $this->type === AlterType::Modify;
    }
}
