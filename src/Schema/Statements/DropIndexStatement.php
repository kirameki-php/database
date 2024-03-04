<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class DropIndexStatement extends BaseStatement
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