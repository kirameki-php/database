<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;

/**
 * @extends ConditionsBuilder<DeleteStatement>
 */
class DeleteBuilder extends ConditionsBuilder
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
        parent::__construct($handler, new DeleteStatement($syntax, $table));
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
