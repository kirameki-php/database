<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class RenameTableStatement extends SchemaStatement
{
    /**
     * @param list<RenameDefinition> $definitions
     */
    public function __construct(
        public array $definitions = [],
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return $syntax->compileRenameTable($this);
    }
}
