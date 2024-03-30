<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use function array_keys;

class InsertStatement extends QueryStatement
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $dataset = [];

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
     * @inheritDoc
     */
    public function prepare(): Executable
    {
        return $this->syntax->compileInsert($this);
    }
}
