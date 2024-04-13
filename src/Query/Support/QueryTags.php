<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, scalar>
 */
class QueryTags implements IteratorAggregate
{
    /**
     * @param array<string, scalar> $pairs
     */
    public function __construct(
        protected array $pairs = [],
    )
    {
    }

    /**
     * @return Traversable<string, scalar>
     */
    public function getIterator(): Traversable
    {
        return yield from $this->pairs;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function add(string $key, mixed $value): static
    {
        $this->pairs[$key] = $value;
        return $this;
    }
}
