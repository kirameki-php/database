<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use function random_int;

class ColumnBuilder
{
    /**
     * @param ColumnDefinition $definition
     */
    public function __construct(
        protected ColumnDefinition $definition,
    )
    {
    }

    /**
     * @return $this
     */
    public function primaryKey(): static
    {
        $this->definition->primaryKey = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function nullable(): static
    {
        $this->definition->nullable = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function autoIncrement(?int $startFrom = null): static
    {
        $this->definition->autoIncrement = $startFrom ?? random_int(1, 1000);
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function default(mixed $value): static
    {
        $this->definition->default = $value;
        return $this;
    }
}
