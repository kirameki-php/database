<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use IteratorAggregate;
use Traversable;
use function array_values;

/**
 * @implements IteratorAggregate<string>
 */
final class Tuple implements IteratorAggregate
{
    /**
     * @var list<mixed>
     */
    public readonly array $items;

    /**
     * @param mixed ...$values
     */
    public function __construct(mixed ...$values)
    {
        $this->items = array_values($values);
    }

    /**
     * @return Traversable<string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
