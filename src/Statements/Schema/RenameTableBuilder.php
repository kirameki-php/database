<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

/**
 * @extends SchemaBuilder<RenameTableStatement>
 */
class RenameTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $from
     * @param string $to
     */
    public function __construct(
        SchemaSyntax $syntax,
        protected string $from,
        protected string $to,
    )
    {
        parent::__construct(new RenameTableStatement($syntax, $from, $to));
    }
}
