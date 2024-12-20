<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class AlterTableStatement extends SchemaStatement
{
    /**
     * @param string $table
     * @param list<mixed> $actions
     */
    public function __construct(
        public readonly string $table,
        public array $actions = [],
    )
    {
    }

    /**
     * @param mixed $action
     */
    public function addAction(mixed $action): void
    {
        $this->actions[] = $action;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return $syntax->compileAlterTable($this);
    }
}
