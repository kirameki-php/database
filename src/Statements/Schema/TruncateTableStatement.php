<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Statement;

class TruncateTableStatement implements Statement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        protected SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
    }

    /**
     * @return list<string>
     */
    public function prepare(): array
    {
        return [
            $this->syntax->formatTruncateTableStatement($this),
        ];
    }
}
