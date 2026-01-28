<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\DatabaseConnection;
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
        $conn = new DatabaseConnection('temp', $adapter, $this->getEventDispatcher());
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
