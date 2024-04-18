<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class Column extends Expression
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
    public function generateTemplate(QuerySyntax $syntax): string
    {
        return $syntax->asColumn($this->column);
    }
}
