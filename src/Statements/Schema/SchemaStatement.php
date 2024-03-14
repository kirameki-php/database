<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Statement;

abstract class SchemaStatement implements Statement
{
    /**
     * @param SchemaSyntax $syntax
     */
    public function __construct(
        protected SchemaSyntax $syntax,
    )
    {
    }

    /**
     * @return list<string>
     */
    abstract public function toExecutables(): array;
}
