<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

use IteratorAggregate;
use Traversable;
use function array_keys;

/**
 * @implements IteratorAggregate<array<string, mixed>>
 */
class Dataset implements IteratorAggregate
{
    /**
     * @param list<array<string, mixed>> $list
     * @param array<string, null> $columns
     */
    public function __construct(
        protected array $list = [],
        protected array $columns = [],
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return yield from $this->list;
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @param iterable<int, iterable<string, mixed>> $dataset
     * @return $this
     */
    public function merge(iterable $dataset): static
    {
        foreach ($dataset as $data) {
            $row = [];
            foreach ($data as $key => $value) {
                $row[$key] = $value;
                $this->columns[$key] = null;
            }
            $this->list[] = $row;
        }
        return $this;
    }
}
