<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

/**
 * @consistent-constructor
 */
class Aggregate extends Expression
{
    /**
     * @param string $column
     * @param string $as
     * @return static
     */
    public static function min(string $column, string $as = 'min'): static
    {
        return new static('MIN', $column, $as);
    }

    /**
     * @param string $column
     * @param string $as
     * @return static
     */
    public static function max(string $column, string $as = 'max'): static
    {
        return new static('MAX', $column, $as);
    }

    /**
     * @param string $column
     * @param string $as
     * @return static
     */
    public static function count(string $column, string $as = 'count'): static
    {
        return new static('COUNT', $column, $as);
    }

    /**
     * @param string $column
     * @param string $as
     * @return static
     */
    public static function avg(string $column, string $as = 'avg'): static
    {
        return new static('AVG', $column, $as);
    }

    /**
     * @param string $column
     * @param string $as
     * @return static
     */
    public static function sum(string $column, string $as = 'sum'): static
    {
        return new static('SUM', $column, $as);
    }

    /**
     * @param string $function
     * @param string|null $column
     * @param string|null $as
     * @param bool $isWindowFunction
     * @param list<string>|null $partitionBy
     * @param array<string, Ordering>|null $orderBy
     */
    public function __construct(
        public readonly string $function,
        public readonly ?string $column = null,
        public readonly ?string $as = null,
        public bool $isWindowFunction = false,
        public ?array $partitionBy = null,
        public ?array $orderBy = null,
    )
    {
    }

    public function over(): WindowBuilder
    {
        return new WindowBuilder($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateTemplate(QuerySyntax $syntax): string
    {
        return $syntax->formatAggregate($this);
    }
}
