<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnBuilderAggregate
{
    /**
     * @param ColumnBuilder $columns
     */
    public function __construct(
        public readonly array $columns,
    )
    {
    }

    /**
     * @return $this
     */
    public function notNull(): static
    {
        foreach ($this->columns as $column) {
            $column->notNull();
        }
        return $this;
    }
}
