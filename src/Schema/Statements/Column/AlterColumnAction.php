<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Schema\Statements\Table\AlterType;

class AlterColumnAction
{
    /**
     * @var ColumnDefinition
     */
    public readonly ColumnDefinition $definition;

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
}
