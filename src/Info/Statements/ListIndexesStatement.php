<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Iterator;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class ListIndexesStatement extends QueryStatement implements Normalizable
{
    /**
     * @var SchemaSyntax
     */
    protected SchemaSyntax $schemaSyntax;

    /**
     * @param DatabaseAdapter $adapter
     * @param string $table
     */
    public function __construct(
        protected readonly DatabaseAdapter $adapter,
        public readonly string $table,
    )
    {
        $this->schemaSyntax = $adapter->getSchemaSyntax();
        parent::__construct($adapter->getQuerySyntax());
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepare(): Executable
    {
        return $this->schemaSyntax->compileListIndexes($this);
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
