<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;

class ListTablesStatement extends QueryStatement
{
    /**
     * @param DatabaseAdapter $adapter
     */
    public function __construct(
        protected readonly DatabaseAdapter $adapter,
    )
    {
        parent::__construct($adapter->getQuerySyntax());
    }

    /**
     * @inheritDoc
     */
    public function prepare(): string
    {
        return $this->adapter->getSchemaSyntax()->compileListTablesStatement($this);
    }

    /**
     * @inheritDoc
     */
    public function getParameters(): array
    {
        return [];
    }
}
