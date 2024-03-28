<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;

class ListTablesStatement extends QueryStatement
{
    public function __construct(
        QuerySyntax $syntax,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): string
    {
        return $this->syntax->compileListTablesStatement($this);
    }

    /**
     * @inheritDoc
     */
    public function getParameters(): array
    {
        return [];
    }
}
