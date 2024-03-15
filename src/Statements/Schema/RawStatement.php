<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

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
    public function toCommands(): array
    {
        return [$this->command];
    }
}
