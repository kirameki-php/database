<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final readonly class Range implements IteratorAggregate
{
    public mixed $lowerBound;
    public bool $lowerClosed;
    public mixed $upperBound;
    public bool $upperClosed;

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function closed(mixed $lower, mixed $upper): self
    {
        return new self($lower, true, $upper, true);
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function open(mixed $lower, mixed $upper): self
    {
        return new self($lower, false, $upper, false);
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function halfOpen(mixed $lower, mixed $upper): self
    {
        return new self($lower, true, $upper, false);
    }

    /**
     * @see closed()
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function included(mixed $lower, mixed $upper): self
    {
        return self::closed($lower, $upper);
    }

    /**
     * @see open()
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function excluded(mixed $lower, mixed $upper): self
    {
        return self::open($lower, $upper);
    }

    /**
     * @see halfOpen()
     * @param mixed $lower
     * @param mixed $upper
     * @return self
     */
    public static function endExcluded(mixed $lower, mixed $upper): self
    {
        return self::halfOpen($lower, $upper);
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
