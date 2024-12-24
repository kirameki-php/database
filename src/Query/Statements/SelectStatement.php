<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class SelectStatement extends ConditionsStatement
{
    /**
     * @param list<string|Expression>|null $tables
     * @param list<string|Expression>|null $columns
     * @param list<JoinDefinition>|null $joins
     * @param list<string>|null $groupBy
     * @param list<ConditionDefinition>|null $having
     * @param int|null $offset
     * @param bool|null $distinct
     * @param Lock|null $lock
     * @param string|null $forceIndex
     * @param CompoundDefinition|null $compound
     */
    public function __construct(
        public ?array $tables = null,
        public ?array $columns = null,
        public ?array $joins = null,
        public ?array $groupBy = null,
        public ?array $having = null,
        public ?int $offset = null,
        public ?bool $distinct = null,
        public ?Lock $lock = null,
        public ?string $forceIndex = null,
        public ?CompoundDefinition $compound = null,
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
        return $syntax->prepareTemplateForSelect($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForSelect($this);
    }
}
