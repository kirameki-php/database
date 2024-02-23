<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\DeleteStatement;

/**
 * @property DeleteStatement $statement;
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new DeleteStatement());
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
        return $this->getQueryFormatter()->formatDeleteStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->formatBindingsForDelete($this->statement);
    }
}
