<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statement;

abstract class QueryStatement implements Statement
{
    /**
     * @param QuerySyntax $syntax
     * @param Tags|null $tags
     */
    public function __construct(
        protected readonly QuerySyntax $syntax,
        public ?Tags $tags = null,
    )
    {
    }

    /**
     * @return Executable<static>
     */
    abstract public function prepare(): Executable;

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->syntax->interpolate($this->prepare());
    }
}
