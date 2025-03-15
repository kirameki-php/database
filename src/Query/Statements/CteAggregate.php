<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, Cte>
 */
class CteAggregate implements IteratorAggregate
{
    /**
     * @param list<Cte> $items
     */
    public function __construct(
        public readonly bool $recursive,
        public array $items = [],
    )
    {
    }

    public function __clone(): void
    {
        $this->items = array_map(static fn($c) => clone $c, $this->items);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @param Cte $cte
     * @return $this
     */
    public function add(Cte $cte): static
    {
        $this->items[] = $cte;
        return $this;
    }
}
