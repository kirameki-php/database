<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Expressions\CurrentTimestamp;

class ColumnBuilder
{
    /**
     * @var ColumnDefinition
     */
    protected ColumnDefinition $definition;

    /**
     * @param ColumnDefinition $definition
     */
    public function __construct(ColumnDefinition $definition)
    {
        $this->definition = $definition;
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
    public function notNull(): static
    {
        $this->definition->nullable = false;
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
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): static
    {
        $this->definition->comment = $comment;
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

    /**
     * @return $this
     */
    public function currentAsDefault(): static
    {
        return $this->default(new CurrentTimestamp());
    }
}
