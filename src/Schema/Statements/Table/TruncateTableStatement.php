<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class TruncateTableStatement extends SchemaStatement
{
    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return $syntax->compileTruncateTable($this);
    }
}
