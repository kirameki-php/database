<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Syntax;

class MySqlSchemaSyntax extends SchemaSyntax
{
    /**
     * @inheritDoc
     */
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
