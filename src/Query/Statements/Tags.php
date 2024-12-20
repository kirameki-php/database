<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, scalar>
 */
final class Tags implements IteratorAggregate
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
     * @param scalar $value
     * @return $this
     */
    public function add(string $key, mixed $value): static
    {
        $this->pairs[$key] = $value;
        return $this;
    }

    /**
     * @param Tags $merge
     * @return $this
     */
    public function merge(Tags $merge): static
    {
        $this->pairs = array_merge($this->pairs, $merge->pairs);
        return $this;
    }
}
