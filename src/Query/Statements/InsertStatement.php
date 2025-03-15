<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class InsertStatement extends QueryStatement
{
    /**
     * @param string $table
     * @param Dataset $dataset
     * @param list<string>|null $returning
     */
    public function __construct(
        public readonly string $table,
        public Dataset $dataset,
        public ?array $returning = null,
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
        return $syntax->prepareTemplateForInsert($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForInsert($this);
    }
}
