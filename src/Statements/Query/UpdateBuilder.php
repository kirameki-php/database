<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;

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
