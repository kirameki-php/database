<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;

class DeleteStatement extends ConditionsStatement
{
    /**
     * @var array<string>|null
     */
    public ?array $returning = null;

    /**
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->syntax->formatDeleteStatement($this);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->syntax->formatBindingsForDelete($this);
    }
}
