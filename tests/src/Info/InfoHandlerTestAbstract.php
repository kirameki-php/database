<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Info;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function dump;

class InfoHandlerTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    public function test_getTableNames(): void
    {
        $connection = $this->getConnection();

        $schema = $connection->schema();
        $schema->createTable('TestA')->run(static fn(CreateTableBuilder $t) => $t->int('id')->primaryKey());
        $schema->createTable('TestB')->run(static fn(CreateTableBuilder $t) => $t->int('id')->primaryKey());

        $tables = $connection->info()->getTableNames();
        $this->assertSame(['TestA', 'TestB'], $tables->all());
    }
}
