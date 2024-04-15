<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class Aggregate extends Expression
{
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
     * @param string $function
     * @param string|null $column
     * @param string|null $as
     */
    public function __construct(
        public readonly string $function,
        public readonly ?string $column = null,
        public readonly ?string $as = null,
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
    public function prepare(QuerySyntax $syntax): string
    {
        return $syntax->formatAggregate($this);
    }
}
