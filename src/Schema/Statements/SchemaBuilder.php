<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\SchemaHandler;

/**
 * @template TSchemaStatement of SchemaStatement
 */
abstract class SchemaBuilder
{
    /**
     * @param TSchemaStatement $statement
     */
    public function __construct(
        protected readonly SchemaHandler $handler,
        protected SchemaStatement $statement,
    )
    {
    }

    /**
     * @return TSchemaStatement
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

    /**
     * @return SchemaResult<TSchemaStatement>
     */
    public function execute(): SchemaResult
    {
        return $this->handler->execute($this->statement);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->handler->toString($this->statement);
    }
}
