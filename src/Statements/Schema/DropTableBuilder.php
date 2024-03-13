<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;

/**
 * @extends SchemaBuilder<DropTableStatement>
 */
class DropTableBuilder extends SchemaBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        public readonly string $table,
    )
    {
        $syntax = $connection->getAdapter()->getSchemaSyntax();
        parent::__construct($connection, new DropTableStatement($syntax, $table));
    }
}
