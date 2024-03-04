<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\Statements\BaseStatement;

abstract class StatementBuilder
{
    protected Formatter $formatter;

    /**
     * @param Connection $connection
     * @param BaseStatement $statement
     */
    public function __construct(
        protected Connection $connection,
        protected BaseStatement $statement,
    )
    {
        $this->formatter = $connection->getQueryFormatter();
    }

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return BaseStatement
     */
    public function getStatement(): BaseStatement
    {
        return $this->statement;
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
    abstract public function prepare(): string;

    /**
     * @return array<mixed>
     */
    abstract public function getBindings(): array;

    /**
     * @return Result
     */
    public function execute(): Result
    {
        return $this->connection->query($this->prepare(), $this->getBindings());
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->formatter->interpolate($this->prepare(), $this->getBindings());
    }
}
