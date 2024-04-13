<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Iterator;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\QueryExecutable;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class ListIndexesStatement extends QueryStatement implements Normalizable
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
     * @return QueryExecutable<self>
     */
    #[Override]
    public function prepare(): QueryExecutable
    {
        return $this->syntax->compileListIndexes($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalize(iterable $rows): Iterator
    {
        return yield from $rows;
    }
}
