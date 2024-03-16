<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

/**
 * @extends SchemaBuilder<DropTableStatement>
 */
class DropTableBuilder extends SchemaBuilder
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
        parent::__construct(new DropTableStatement($syntax, $table));
    }
}
