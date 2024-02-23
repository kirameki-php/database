<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\UpdateStatement;

/**
 * @property UpdateStatement $statement
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new UpdateStatement());
    }

    /**
     * @param string $name
     * @return $this
     */
    public function table(string $name): static
    {
        $this->statement->table = $name;
        return $this;
    }

    /**
     * @param array<string, mixed> $assignments
     * @return $this
     */
    public function set(array $assignments): static
    {
        $this->statement->data = $assignments;
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = $columns;
        return $this;
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatUpdateStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->formatBindingsForUpdate($this->statement);
    }
}
