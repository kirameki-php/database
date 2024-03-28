<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Iterator;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;

class ColumnsInfoStatement extends QueryStatement implements Normalizable
{
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
    public function prepare(): string
    {
        return $this->schemaSyntax->compileColumnsInfoStatement($this);
    }

    /**
     * @inheritDoc
     */
    public function getParameters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function normalize(iterable $rows): Iterator
    {
        return $this->schemaSyntax->normalizeColumnInfoStatement($rows);
    }
}
