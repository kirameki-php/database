<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\QueryExecutable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Override;

class ListTablesStatement extends QueryStatement
{
    /**
     * @inheritDoc
     * @return QueryExecutable<self>
     */
    #[Override]
    public function prepare(): QueryExecutable
    {
        return $this->syntax->compileListTables($this);
    }
}
