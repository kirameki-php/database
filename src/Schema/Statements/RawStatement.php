<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class RawStatement extends SchemaStatement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $command
     */
    public function __construct(
        SchemaSyntax $syntax,
        protected string $command,
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
        return [$this->command];
    }
}
