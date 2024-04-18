<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use function array_keys;

class UpsertStatement extends QueryStatement
{
    /**
     * @param list<array<string, mixed>> $dataset
     * @param list<string> $onConflict
     * @param list<string>|null $returning
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
        public array $dataset = [],
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
        return $syntax->prepareTemplateForUpsert($this, $this->getDatasetColumns($this));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForUpsert($this, $this->getDatasetColumns($this));
    }

    /**
     * @param static $statement
     * @return list<string>
     */
    protected function getDatasetColumns(self $statement): array
    {
        $columnsMap = [];
        foreach ($statement->dataset as $data) {
            foreach (array_keys($data) as $name) {
                $columnsMap[$name] = null;
            }
        }
        return array_keys($columnsMap);
    }
}
