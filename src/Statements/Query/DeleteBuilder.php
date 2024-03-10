<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Connection;

/**
 * @extends ConditionsBuilder<DeleteStatement>
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     * @param DeleteStatement $statement
     */
    public function __construct(
        Connection $connection,
        DeleteStatement $statement,
    )
    {
        parent::__construct($connection, $statement);
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
