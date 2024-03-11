<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Statement;

abstract class QueryStatement implements Statement
{
    public function __construct(
        protected readonly QuerySyntax $syntax,
    )
    {
    }

    /**
     * @return string
     */
    abstract public function prepare(): string;

    /**
     * @return array<mixed>
     */
    abstract public function getParameters(): array;

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->syntax->interpolate($this);
    }
}
