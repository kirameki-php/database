<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Result;
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
     * @param Connection $connection
     * @param InsertStatement $statement
     */
    public function __construct(
        Connection $connection,
        InsertStatement $statement,
    )
    {
        parent::__construct($connection, $statement);
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
     * @return Result
     */
    public function execute(): Result
    {
        if (count($this->statement->dataset) === 0) {
            throw new LogicException('Values must be set in order to execute an insert query');
        }
        return parent::execute();
    }
}
