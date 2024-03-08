<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;

/**
 * @template-covariant TStatement of Statement
 */
abstract class StatementBuilder implements Builder
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
     * @return string[]
     */
    abstract public function build(): array;

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(PHP_EOL, $this->build());
    }
}
