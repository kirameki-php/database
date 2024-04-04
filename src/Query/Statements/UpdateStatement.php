<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @var array<string, mixed>
     */
    public array $data;

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
    #[Override]
    public function prepare(): Executable
    {
        return $this->syntax->compileUpdate($this);
    }
}
