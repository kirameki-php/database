<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, scalar>
 */
final class Tags implements IteratorAggregate, Countable
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
     * @return int
     */
    public function count(): int
    {
        return count($this->pairs);
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return $this
     */
    public function set(string $key, mixed $value): self
    {
        $this->pairs[$key] = $value;
        return $this;
    }

    /**
     * @param Tags $merge
     * @return $this
     */
    public function merge(Tags $merge): self
    {
        $this->pairs = array_merge($this->pairs, $merge->pairs);
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
