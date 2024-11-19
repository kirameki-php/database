<?php declare(strict_types=1);

namespace Kirameki\Database\Info\Statements;

use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Support\ReferenceOption;
use Override;
use stdClass;
use function explode;

class ListForeignKeysStatement extends QueryStatement implements Normalizable
{
    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateTemplate(QuerySyntax $syntax): string
    {
        return $syntax->prepareTemplateForListForeignKeys($this);
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
        $row->columns = explode(',', $row->columns);
        $row->referencedColumns = explode(',', $row->referencedColumns);
        $row->onUpdate = ReferenceOption::from($row->onUpdate);
        $row->onDelete = ReferenceOption::from($row->onDelete);
        return $row;
    }
}
