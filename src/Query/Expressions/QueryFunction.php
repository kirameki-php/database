<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @implements Expression<QuerySyntax>
 */
abstract class QueryFunction implements Expression
{
    /**
     * @var string
     */
    public static string $name;

    /**
     * @var string
     */
    public static string $defaultAlias;

    /**
     * @var bool
     */
    public bool $isWindowFunction = false;

    /**
     * @var list<string>|null
     */
    public ?array $partitionBy = null;

    /**
     * @var array<string, Ordering>|null
     */
    public ?array $orderBy = null;

    /**
     * @param string|Expression|null $column
     * @param string|null $as
     */
    public function __construct(
        public readonly string|Expression|null $column = null,
        public ?string $as = null,
    )
    {
        $this->as ??= static::$defaultAlias;
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
        return $syntax->formatFunction($this);
    }
}
