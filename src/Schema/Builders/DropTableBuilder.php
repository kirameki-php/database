<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropTableStatement;

/**
 * @extends StatementBuilder<DropTableStatement>
 */
class DropTableBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        string $table,
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