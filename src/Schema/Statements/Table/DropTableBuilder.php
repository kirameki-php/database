<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;

/**
 * @extends SchemaBuilder<DropTableStatement>
 */
class DropTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
    )
    {
        parent::__construct($handler, new DropTableStatement($table));
    }
}
