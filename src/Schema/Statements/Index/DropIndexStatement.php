<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class DropIndexStatement extends SchemaStatement
{
    /**
     * @param string $table
     * @param string $name
     */
    public function __construct(
        public readonly string $table,
        public readonly string $name,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return $syntax->compileDropIndex($this);
    }
}
