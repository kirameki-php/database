<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

readonly class ColumnBuilderAggregate
{
    /**
     * @param list<ColumnBuilder> $columns
     */
    public function __construct(
        protected array $columns,
    )
    {
    }

    /**
     * @return $this
     */
    public function nullable(bool $toggle = true): static
    {
        foreach ($this->columns as $column) {
            $column->nullable($toggle);
        }
        return $this;
    }
}
