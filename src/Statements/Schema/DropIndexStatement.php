<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class DropIndexStatement extends Statement
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string[]
     */
    public array $columns = [];
}