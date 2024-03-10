<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Connection;

/**
 * @extends ConditionsBuilder<UpdateStatement>
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     * @param UpdateStatement $statement
     */
    public function __construct(
        Connection $connection,
        UpdateStatement $statement,
    )
    {
        parent::__construct($connection, $statement);
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
}
