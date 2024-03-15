<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\SchemaHandler;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

/**
 * @extends SchemaBuilder<RenameTableStatement>
 */
class RenameTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param SchemaSyntax $syntax
     * @param string $from
     * @param string $to
     */
    public function __construct(
        SchemaHandler $handler,
        SchemaSyntax $syntax,
        protected string $from,
        protected string $to,
    )
    {
        parent::__construct($handler, new RenameTableStatement($syntax, $from, $to));
    }
}
