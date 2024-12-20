<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final readonly class Bounds implements IteratorAggregate
{
    public mixed $lower;
    public bool $lowerClosed;
    public mixed $upper;
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
     * @param mixed $lower
     * @param bool $lowerClosed
     * @param mixed $upper
     * @param bool $upperClosed
     */
    public function __construct(mixed $lower, bool $lowerClosed, mixed $upper, bool $upperClosed)
    {
        $this->lower = $lower;
        $this->lowerClosed = $lowerClosed;
        $this->upper = $upper;
        $this->upperClosed = $upperClosed;
    }

    /**
     * @return Traversable<mixed>
     */
    public function getIterator(): Traversable
    {
        yield 0 => $this->lower;
        yield 1 => $this->upper;
    }

    /**
     * @param bool $negated
     * @return string
     */
    public function getLowerOperator(bool $negated = false): string
    {
        return $negated
            ? ($this->lowerClosed ? '<' : '<=')
            : ($this->lowerClosed ? '>=' : '>');
    }

    /**
     * @param bool $negated
     * @return string
     */
    public function getUpperOperator(bool $negated = false): string
    {
        return $negated
            ? ($this->upperClosed ? '>' : '>=')
            : ($this->upperClosed ? '<=' : '<');
    }
}
