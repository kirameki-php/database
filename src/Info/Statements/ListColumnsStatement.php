<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Iterator;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class ListColumnsStatement extends QueryStatement implements Normalizable
{
    /**
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     * @return Executable<self>
     */
    #[Override]
    public function prepare(): Executable
    {
        return $this->syntax->compileListColumns($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalize(iterable $rows): Iterator
    {
        return $this->syntax->normalizeListColumns($rows);
    }
}
