<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\SchemaHandler;

/**
 * @template TStatement of SchemaStatement
 */
abstract class SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param TStatement $statement
     */
    public function __construct(
        protected readonly SchemaHandler $handler,
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

    /**
     * @return SchemaExecution<TStatement>
     */
    public function execute(): SchemaExecution
    {
        return $this->handler->execute($this->statement);
    }
}
