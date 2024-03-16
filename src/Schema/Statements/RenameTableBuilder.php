<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

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
