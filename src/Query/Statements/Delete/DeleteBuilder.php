<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements\Delete;

use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Statements\ConditionsBuilder;
use function array_is_list;
use function array_values;

/**
 * @extends ConditionsBuilder<DeleteStatement>
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param QueryHandler $handler
     * @param string $table
     */
    public function __construct(QueryHandler $handler, string $table)
    {
        parent::__construct($handler, new DeleteStatement($table));
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
    }
}
