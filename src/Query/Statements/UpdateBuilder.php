<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use function array_values;
use function iterator_to_array;

/**
 * @extends ConditionsBuilder<UpdateStatement>
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @param QueryHandler $handler
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QueryHandler $handler,
        QuerySyntax $syntax,
        string $table,
    )
    {
        parent::__construct($handler, new UpdateStatement($syntax, $table));
    }

    /**
     * @param iterable<string, mixed> $assignments
     * @return $this
     */
    public function set(iterable $assignments): static
    {
        $this->statement->data = iterator_to_array($assignments);
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = array_values($columns);
        return $this;
    }
}
