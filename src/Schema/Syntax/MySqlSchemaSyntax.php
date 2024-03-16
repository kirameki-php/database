<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

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
