<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class UpdateStatement extends ConditionStatement
{
    /**
     * @param string $table
     * @param array<string, mixed>|null $set
     * @param list<string>|null $returning
     */
    public function __construct(
        public readonly string $table,
        public ?array $set = null,
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
        return $syntax->prepareTemplateForUpdate($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForUpdate($this);
    }
}
