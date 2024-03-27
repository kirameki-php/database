<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;

class ColumnsInfoStatement extends QueryStatement
{
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    public function prepare(): string
    {
        return $this->syntax->compileColumnsInfoStatement($this);
    }

    public function getParameters(): array
    {
        return [];
    }
}
