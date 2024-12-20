<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Schema\Support\AlterType;

class AlterColumnAction
{
    /**
     * @var ColumnDefinition
     */
    public readonly ColumnDefinition $definition;

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
     * @param string $name
     */
    public function __construct(
        public readonly AlterType $type,
        string $name,
    )
    {
        $this->definition = new ColumnDefinition($name);
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
