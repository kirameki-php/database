<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

/**
 * @template TSchemaStatement of SchemaStatement
 */
abstract class SchemaBuilder
{
    /**
     * @param TSchemaStatement $statement
     */
    public function __construct(
        protected SchemaStatement $statement,
    )
    {
    }

    /**
     * @return SchemaStatement
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
    public function __clone(): void
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
