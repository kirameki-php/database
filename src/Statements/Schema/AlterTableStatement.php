<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

class AlterTableStatement extends SchemaStatement
{
    /**
     * @var list<mixed>
     */
    public array $actions;

    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @param mixed $action
     */
    public function addAction(mixed $action): void
    {
        $this->actions[] = $action;
    }

    /**
     * @return list<string>
     */
    public function prepare(): array
    {
        $syntax = $this->syntax;
        $statements = [];
        foreach ($this->actions as $action) {
            if ($action instanceof AlterColumnAction) {
                $statements[] = $syntax->formatAlterColumnAction($action);
            }
            elseif ($action instanceof AlterDropColumnAction) {
                $statements[] = $syntax->formatDropColumnAction($action);
            }
            elseif ($action instanceof AlterRenameColumnAction) {
                $statements[] = $syntax->formatRenameColumnAction($action);
            }
            elseif ($action instanceof CreateIndexStatement) {
                $statements[] = $syntax->formatCreateIndexStatement($action);
            }
            elseif ($action instanceof DropIndexStatement) {
                $statements[] = $syntax->formatDropIndexStatement($action);
            }
        }
        return $statements;
    }
}
