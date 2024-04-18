<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use function array_keys;

class InsertStatement extends QueryStatement
{
    /**
     * @param string $table
     * @param list<array<string, mixed>> $dataset
     * @param list<string>|null $returning
     */
    public function __construct(
        public readonly string $table,
        public array $dataset = [],
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
        return $syntax->prepareTemplateForInsert($this, $this->getDatasetColumns($this));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForInsert($this, $this->getDatasetColumns($this));
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
