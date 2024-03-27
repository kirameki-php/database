<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

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
    public function autoIncrement(): static
    {
        $this->definition->autoIncrement = true;
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
