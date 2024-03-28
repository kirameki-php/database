<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Iterator;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;

class ColumnsInfoStatement extends QueryStatement implements Normalizable
{
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): string
    {
        return $this->syntax->compileColumnsInfoStatement($this);
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
        return $this->syntax->normalizeColumnInfoStatement($rows);
    }
}
