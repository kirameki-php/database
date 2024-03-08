<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * @var array<string>|null
     */
    public ?array $returning = null;

    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }
}
