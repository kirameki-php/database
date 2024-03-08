<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Schema\Support\AlterType;

class AlterColumnAction
{
    public ?string $positionType = null;

    public ?string $positionColumn = null;

    public function __construct(
        public readonly AlterType $type,
        public readonly ColumnDefinition $definition,
    )
    {
    }

    public function isAdd(): bool
    {
        return $this->type === AlterType::Add;
    }

    public function isModify(): bool
    {
        return $this->type === AlterType::Modify;
    }
}
