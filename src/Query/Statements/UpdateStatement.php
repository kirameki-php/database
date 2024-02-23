<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * @var array<string>|null
     */
    public ?array $returning = null;
}
