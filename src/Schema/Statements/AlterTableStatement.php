<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterTableStatement extends BaseStatement
{
    /**
     * @var array<mixed>
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