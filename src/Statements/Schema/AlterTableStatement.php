<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class AlterTableStatement extends SchemaStatement
{
    /**
     * @var list<mixed>
     */
    public array $actions;

    /**
     * @param mixed $action
     */
    public function addAction(mixed $action): void
    {
        $this->actions[] = $action;
    }
}
