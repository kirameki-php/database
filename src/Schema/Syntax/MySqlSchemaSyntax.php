<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use function implode;

class MySqlSchemaSyntax extends SchemaSyntax
{
    /**
     * @param ColumnDefinition $def
     * @return string
     */
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = parent::formatColumnDefinition($def);

        if ($def->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $parts);
    }

    /**
     * @inheritDoc
     */
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
