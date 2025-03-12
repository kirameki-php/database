<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use function array_is_list;
use function array_values;
use function iterator_to_array;

/**
 * @extends WhereBuilder<UpdateStatement>
 */
class UpdateBuilder extends WhereBuilder
{
    /**
     * @param QueryHandler $handler
     * @param string $table
     */
    public function __construct(QueryHandler $handler, string $table)
    {
        parent::__construct($handler, new UpdateStatement($table));
    }

    /**
     * @param iterable<string, mixed> $assignments
     * @return $this
     */
    public function set(iterable $assignments): static
    {
        $this->statement->set = iterator_to_array($assignments);
        return $this;
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
