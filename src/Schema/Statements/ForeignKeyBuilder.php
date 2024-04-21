<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Support\ReferenceOption;

readonly class ForeignKeyBuilder
{
    public function __construct(
        public ForeignKeyConstraint $constraint,
    )
    {
    }

    /**
     * @param string|null $name
     * @return static
     */
    public function name(?string $name): static
    {
        $this->constraint->name = $name;
        return $this;
    }

    /**
     * @param ReferenceOption|null $option
     * @return $this
     */
    public function onDelete(?ReferenceOption $option): static
    {
        $this->constraint->onDelete = $option;
        return $this;
    }

    /**
     * @param ReferenceOption|null $option
     * @return $this
     */
    public function onUpdate(?ReferenceOption $option): static
    {
        $this->constraint->onUpdate = $option;
        return $this;
    }
}