<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class UpsertStatement extends QueryStatement
{
    /**
     * @param QuerySyntax $syntax
     * @param list<array<string, mixed>> $dataset
     * @param list<string> $onConflict
     * @param list<string>|null $returning
     * @param string $table
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
        public array $dataset = [],
        public array $onConflict = [],
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
        return $this->syntax->compileUpsert($this);
    }
}
