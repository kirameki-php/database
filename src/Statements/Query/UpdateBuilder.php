<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\QueryHandler;

/**
 * @extends ConditionsBuilder<UpdateStatement>
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @param QueryHandler $handler
     * @param UpdateStatement $statement
     */
    public function __construct(
        QueryHandler $handler,
        UpdateStatement $statement,
    )
    {
        parent::__construct($handler, $statement);
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
