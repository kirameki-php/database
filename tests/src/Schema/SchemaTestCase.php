<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class SchemaTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function createTableBuilder(string $table): CreateTableBuilder
    {
        return new CreateTableBuilder($this->mysqlConnection()->schema(), $table);
    }
}
