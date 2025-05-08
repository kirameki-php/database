<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Query\Statements\DeleteBuilder;

class DeleteBuilderSqliteTest extends DeleteBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_delete__all_with_drop_protection_enabled(): void
    {
        $this->expectException(DropProtectionException::class);
        $this->expectExceptionMessage('DELETE without a WHERE clause is prohibited by configuration.');

        $adapter = new SqliteAdapter(
            new DatabaseConfig([], dropProtection: true),
            new SqliteConfig(':memory:'),
        );
        $conn = new Connection('temp', $adapter, $this->getEventManager());
        $builder = new DeleteBuilder($conn->query(), 'User');
        $builder->execute();
    }

    public function test_delete__all_with_drop_protection_disabled(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $handler = $conn->query();
        $handler->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $result = $handler->deleteFrom('User')->execute();

        $this->assertSame(2, $result->affectedRowCount);
        $this->assertSame([], $handler->select()->from('User')->execute()->all());
    }
}
