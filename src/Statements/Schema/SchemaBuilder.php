<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

/**
 * @template TStatement of SchemaStatement
 */
abstract class SchemaBuilder
{
    /**
     * @param TStatement $statement
     */
    public function __construct(
        protected SchemaStatement $statement,
    )
    {
    }

    /**
     * @return TStatement
     */
    public function getStatement(): SchemaStatement
    {
        return $this->statement;
    }

    /**
     * Do a deep clone of object types
     *
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @return static
     */
    protected function copy(): static
    {
        return clone $this;
    }
}
