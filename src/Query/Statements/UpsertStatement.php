<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\Dataset;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class UpsertStatement extends QueryStatement implements ManipulationStatement
{
    /**
     * @param Dataset $dataset
     * @param list<string> $onConflict
     * @param list<string>|null $returning
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
        public Dataset $dataset,
        public array $onConflict = [],
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
        return $syntax->prepareTemplateForUpsert($this, $this->dataset->getColumns());
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForUpsert($this, $this->dataset->getColumns());
    }
}
