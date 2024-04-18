<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

/**
 * @extends SchemaBuilder<DropTableStatement>
 */
class DropTableBuilder extends SchemaBuilder
{
    /**
     * @param string $table
     */
    public function __construct(
        string $table,
    )
    {
        parent::__construct(new DropTableStatement($table));
    }
}
