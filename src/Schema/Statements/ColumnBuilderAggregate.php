<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnBuilderAggregate
{
    /**
     * @param list<ColumnBuilder> $columns
     */
    public function __construct(
        public readonly array $columns,
    )
    {
    }

    /**
     * @return $this
     */
    public function nullable(): static
    {
        foreach ($this->columns as $column) {
            $column->nullable();
        }
        return $this;
    }
}
