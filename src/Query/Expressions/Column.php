<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @implements Expression<QuerySyntax>
 */
class Column implements Expression
{
    /**
     * @param string $column
     */
    public function __construct(
        public readonly string $column,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->asColumn($this->column);
    }
}
