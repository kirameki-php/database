<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Support\ReferenceOption;
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

    /**
     * @param string $table
     * @param string $column
     * @param ReferenceOption|null $onDelete
     * @param ReferenceOption|null $onUpdate
     * @return $this
     */
    public function references(
        string $table,
        string $column,
        ReferenceOption $onDelete = null,
        ReferenceOption $onUpdate = null,
    ): static
    {
        $this->definition->references = new ForeignKeyConstraint(
            [$this->definition->name],
            $table,
            [$column],
            null,
            $onDelete,
            $onUpdate,
        );
        return $this;
    }
}
