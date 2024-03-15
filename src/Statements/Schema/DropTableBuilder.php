<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\SchemaHandler;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

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
