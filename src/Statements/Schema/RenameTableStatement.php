<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Statement;

class RenameTableStatement implements Statement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $from
     * @param string $to
     */
    public function __construct(
        protected SchemaSyntax $syntax,
        public readonly string $from,
        public readonly string $to,
    )
    {
    }

    /**
     * @return list<string>
     */
    public function prepare(): array
    {
        return [
            $this->syntax->formatRenameTableStatement($this),
        ];
    }
}
