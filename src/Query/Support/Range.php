<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
class Range implements IteratorAggregate
{
    public readonly mixed $lowerBound;
    public readonly bool $lowerClosed;
    public readonly mixed $upperBound;
    public readonly bool $upperClosed;

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function closed(mixed $lower, mixed $upper): static
    {
        return new static($lower, true, $upper, true);
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function open(mixed $lower, mixed $upper): static
    {
        return new static($lower, false, $upper, false);
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function halfOpen(mixed $lower, mixed $upper): static
    {
        return new static($lower, true, $upper, false);
    }

    /**
     * @see closed()
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function included(mixed $lower, mixed $upper): static
    {
        return static::closed($lower, $upper);
    }

    /**
     * @see open()
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function excluded(mixed $lower, mixed $upper): static
    {
        return static::open($lower, $upper);
    }

    /**
     * @see halfOpen()
     * @param mixed $lower
     * @param mixed $upper
     * @return static
     */
    public static function endExcluded(mixed $lower, mixed $upper): static
    {
        return static::halfOpen($lower, $upper);
    }

    /**
     * @param mixed $lowerBound
     * @param bool $lowerClosed
     * @param mixed $upperBound
     * @param bool $upperClosed
     */
    public function __construct(mixed $lowerBound, bool $lowerClosed, mixed $upperBound, bool $upperClosed)
    {
        $this->lowerBound = $lowerBound;
        $this->lowerClosed = $lowerClosed;
        $this->upperBound = $upperBound;
        $this->upperClosed = $upperClosed;
    }

    /**
     * @return Traversable<mixed>
     */
    public function getIterator(): Traversable
    {
        yield 0 => $this->lowerBound;
        yield 1 => $this->upperBound;
    }
}
