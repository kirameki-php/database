<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

class AlterTableStatement extends Statement
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