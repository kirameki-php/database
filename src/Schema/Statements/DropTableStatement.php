<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

class DropTableStatement extends SchemaStatement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @return list<string>
     */
    public function toCommands(): array
    {
        return [
            $this->syntax->formatDropTableStatement($this),
        ];
    }
}
