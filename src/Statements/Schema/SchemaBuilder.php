<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\OldStatementBuilder;

/**
 * @template-covariant TStatement of Statement
 */
abstract class SchemaBuilder implements OldStatementBuilder
{
    /**
     * @param Connection $connection
     * @param TStatement $statement
     */
    public function __construct(
        protected Connection $connection,
        protected Statement $statement
    )
    {
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
     * @return string
     */
    public function toString(): string
    {
        return implode(PHP_EOL, $this->build());
    }
}
