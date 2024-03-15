<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use LogicException;
use Traversable;
use function count;
use function iterator_to_array;

/**
 * @extends QueryBuilder<InsertStatement>
 */
class InsertBuilder extends QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QueryHandler $handler,
        QuerySyntax $syntax,
        string $table
    )
    {
        parent::__construct($handler, new InsertStatement($syntax, $table));
    }

    /**
     * @param array<string, mixed> $data
     * @return $this
     */
    public function value(array $data): static
    {
        $this->statement->dataset = [$data];
        return $this;
    }

    /**
     * @param iterable<array<string, mixed>> $dataset
     * @return $this
     */
    public function values(iterable $dataset): static
    {
        $dataset = ($dataset instanceof Traversable) ? iterator_to_array($dataset) : $dataset;
        $this->statement->dataset = $dataset;
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

    /**
     * @return QueryResult<InsertStatement>
     */
    public function execute(): QueryResult
    {
        if (count($this->statement->dataset) === 0) {
            throw new LogicException('Values must be set in order to execute an insert query');
        }
        return parent::execute();
    }
}
