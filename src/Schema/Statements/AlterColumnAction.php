<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Support\AlterType;

class AlterColumnAction
{
    public AlterType $type;

    public ColumnDefinition $definition;

    public string $positionType;

    public ?string $positionColumn;

    public function __construct(AlterType $type, ColumnDefinition $definition)
    {
        $this->type = $type;
        $this->definition = $definition;
        $this->positionColumn = null;
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
