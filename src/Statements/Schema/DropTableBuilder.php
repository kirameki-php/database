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
        parent::__construct($connection, new DropTableStatement($table));
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->formatDropTableStatement($this->statement)
        ];
    }
}