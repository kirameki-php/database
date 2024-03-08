<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Result;
use LogicException;
use Traversable;
use function count;
use function iterator_to_array;

/**
 * @extends StatementBuilder<InsertStatement>
 */
class InsertBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        string $table,
    )
    {
        parent::__construct($connection, $this->createStatement($table));
    }

    /**
     * @param string $table
     * @return InsertStatement
     */
    protected function createStatement(string $table): InsertStatement
    {
        return new InsertStatement($table);
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

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->formatter->formatInsertStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->formatter->formatBindingsForInsert($this->statement);
    }
}
