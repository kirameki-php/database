<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\QueryHandler;

/**
 * @extends ConditionsBuilder<DeleteStatement>
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param QueryHandler $handler
     * @param DeleteStatement $statement
     */
    public function __construct(
        QueryHandler $handler,
        DeleteStatement $statement,
    )
    {
        parent::__construct($handler, $statement);
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
