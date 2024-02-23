<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

class ColumnBuilderAggregate
{
    /**
     * @var array<ColumnBuilder>
     */
    public array $columns;

    /**
     * @param array<ColumnBuilder> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
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
