<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\QueryTags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statement;

abstract class QueryStatement implements Statement
{
    /**
     * @param QuerySyntax $syntax
     * @param QueryTags|null $tags
     */
    public function __construct(
        protected readonly QuerySyntax $syntax,
        public ?QueryTags $tags = null,
    )
    {
    }

    /**
     * @return Executable
     */
    abstract public function prepare(): Executable;

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->syntax->interpolate($this);
    }
}
