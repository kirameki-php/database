<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class SchemaTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function createTableBuilder(string $table, bool $temporary = false): CreateTableBuilder
    {
        $schema = $this->connection($this->connection)->schema();
        return new CreateTableBuilder($schema, $table, $temporary);
    }
}
