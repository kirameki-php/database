<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

/**
 * @consistent-constructor
 */
abstract class Aggregate extends QueryFunction
{
    /**
     * @param string $column
     * @param string|null $as
     * @return static
     */
    public static function column(string $column, ?string $as = null): static
    {
        return new static($column, $as);
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
     */
    public function __construct(
        string $column,
        ?string $as = null,
    )
    {
        parent::__construct($column, $as);
    }
}
