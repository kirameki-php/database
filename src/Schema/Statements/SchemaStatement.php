<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

abstract class SchemaStatement
{
    /**
     * @return list<string>
     */
    abstract public function toExecutable(SchemaSyntax $syntax): array;

    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    public function toString(SchemaSyntax $syntax): string
    {
        return implode(";\n", $this->toExecutable($syntax)) . ';';
    }
}
