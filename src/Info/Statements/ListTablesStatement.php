<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use stdClass;

class ListTablesStatement extends QueryStatement implements Normalizable
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function generateTemplate(QuerySyntax $syntax): string
    {
        return $syntax->prepareTemplateForListTables($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalize(QuerySyntax $syntax, stdClass $row): ?stdClass
    {
        return $syntax->normalizeListTables($row);
    }
}
