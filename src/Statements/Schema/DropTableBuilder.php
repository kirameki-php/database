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
     * @param SchemaHandler $handler
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        SchemaHandler $handler,
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($handler, new DropTableStatement($syntax, $table));
    }
}
