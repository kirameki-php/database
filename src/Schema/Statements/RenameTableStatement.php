<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class RenameTableStatement extends SchemaStatement
{
    /**
     * @param string $from
     * @param string $to
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toCommands(SchemaSyntax $syntax): array
    {
        return $syntax->compileRenameTable($this);
    }
}
