<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Query\Statements\DeleteBuilder;

class DeleteBuilderMySqlTest extends DeleteBuilderTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_delete__all_with_drop_protection_enabled(): void
    {
        $this->expectException(DropProtectionException::class);
        $this->expectExceptionMessage('DELETE without a WHERE clause is prohibited by configuration.');

        $adapter = new MySqlAdapter(
            new DatabaseConfig([], dropProtection: true),
            new MySqlConfig('mysql'),
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

    public function test_delete_all(): void
    {
        $sql = $this->deleteBuilder('User')->toSql();
        $this->assertSame("DELETE FROM `User`", $sql);
    }

    public function test_delete_with_where(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->toSql();
        $this->assertSame("DELETE FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->returning('id', 'name')->toSql();
        $this->assertSame("DELETE FROM `User` WHERE `id` = 1 RETURNING `id`, `name`", $sql);
    }
}
