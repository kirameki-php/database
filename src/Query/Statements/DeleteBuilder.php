<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use function array_is_list;
use function array_values;

/**
 * @template TRow of mixed = mixed
 * @extends WhereBuilder<DeleteStatement, TRow>
 */
class DeleteBuilder extends WhereBuilder
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
