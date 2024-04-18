<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use function array_values;
use function iterator_to_array;

/**
 * @extends QueryBuilder<UpsertStatement>
 */
class UpsertBuilder extends QueryBuilder
{
    /**
     * @param iterable<string, mixed> $data
     * @return $this
     */
    public function value(iterable $data): static
    {
        return $this->values([$data]);
    }

    /**
     * @param iterable<int, iterable<string, mixed>> $dataset
     * @return $this
     */
    public function values(iterable $dataset): static
    {
        $statement = $this->statement;
        foreach ($dataset as $data) {
            $statement->dataset[] = iterator_to_array($data);
        }
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function onConflict(string ...$columns): static
    {
        $this->statement->onConflict = array_values($columns);
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
