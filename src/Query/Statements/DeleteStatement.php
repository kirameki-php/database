<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class DeleteStatement extends ConditionsStatement
{
    /**
     * @param QuerySyntax $syntax
     * @param string $table
     * @param list<string>|null $returning
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
        public ?array $returning = null,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     * @return QueryExecutable<self>
     */
    #[Override]
    public function prepare(): QueryExecutable
    {
        return $this->syntax->compileDelete($this);
    }
}
