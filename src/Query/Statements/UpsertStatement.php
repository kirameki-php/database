<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;

class UpsertStatement extends QueryStatement
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $dataset = [];

    /**
     * @var list<string>
     */
    public array $onConflict = [];

    /**
     * @var list<string>|null
     */
    public ?array $returning = null;

    /**
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): Executable
    {
        return $this->syntax->compileUpsert($this);
    }
}
