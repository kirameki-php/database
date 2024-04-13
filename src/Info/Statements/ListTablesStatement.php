<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Override;

class ListTablesStatement extends QueryStatement
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function prepare(): Executable
    {
        return $this->syntax->compileListTables($this);
    }
}
