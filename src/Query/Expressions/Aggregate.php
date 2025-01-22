<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @consistent-constructor
 * @implements Expression<QuerySyntax>
 */
abstract class Aggregate implements Expression
{
    public static string $function;

    protected static string $defaultAlias;

    /**
     * @param string $column
     * @param string|null $as
     * @return static
     */
    public static function column(string $column, ?string $as = null): static
    {
        return new static($column, $as ?? static::$defaultAlias);
    }

    /**
     * @param string|null $as
     * @return static
     */
    public static function all(?string $as = null): static
    {
        return static::column('*', $as);
    }

    /**
     * @param string $column
     * @param string|null $as
     * @param bool $isWindowFunction
     * @param list<string>|null $partitionBy
     * @param array<string, Ordering>|null $orderBy
     */
    public function __construct(
        public readonly string $column,
        public readonly ?string $as = null,
        public bool $isWindowFunction = false,
        public ?array $partitionBy = null,
        public ?array $orderBy = null,
    )
    {
    }

    /**
     * @return WindowBuilder
     */
    public function over(): WindowBuilder
    {
        return new WindowBuilder($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->formatAggregate($this);
    }
}
