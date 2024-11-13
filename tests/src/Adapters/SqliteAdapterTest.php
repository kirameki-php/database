<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;

class SqliteAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'sqlite';

    #[Override]
    public function test_beginTransaction_with_isolation_level(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Transaction Isolation level cannot be changed in SQLite.');
        $adapter = $this->createConnection()->adapter;
        $adapter->beginTransaction(IsolationLevel::Serializable);
    }
}
