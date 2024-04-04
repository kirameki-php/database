<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

class RenameTableStatement extends SchemaStatement
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
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toCommands(): array
    {
        return $this->syntax->compileRenameTable($this);
    }
}
