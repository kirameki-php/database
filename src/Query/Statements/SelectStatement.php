<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class SelectStatement extends ConditionStatement
{
    /**
     * @param list<string|Expression>|null $tables
     * @param list<string|Expression>|null $columns
     * @param list<JoinDefinition>|null $joins
     * @param list<string>|null $groupBy
     * @param Condition|null $having
     * @param array<string, Ordering>|null $orderBy
     * @param int|null $offset
     * @param int|null $limit
     * @param bool|null $distinct
     * @param Lock|null $lock
     * @param string|null $forceIndex
     * @param Compound|null $compound
     */
    public function __construct(
        public ?array $tables = null,
        public ?array $columns = null,
        public ?array $joins = null,
        public ?array $groupBy = null,
        public ?Condition $having = null,
        public ?array $orderBy = null,
        public ?int $offset = null,
        public ?int $limit = null,
        public ?bool $distinct = null,
        public ?Lock $lock = null,
        public ?string $forceIndex = null,
        public ?Compound $compound = null,
    )
    {
        parent::__construct();
    }

    public function __clone(): void
    {
        parent::__clone();

        if ($this->having !== null) {
            $this->having = clone $this->having;
        }
        if ($this->compound !== null) {
            $this->compound = clone $this->compound;
        }
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
