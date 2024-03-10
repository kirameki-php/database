<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Formatters\QueryFormatter;

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
     * @param QueryFormatter $formatter
     * @param string $table
     */
    public function __construct(
        QueryFormatter $formatter,
        public readonly string $table,
    )
    {
        parent::__construct($formatter);
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->formatter->formatUpdateStatement($this);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->formatter->formatBindingsForUpdate($this);
    }
}
