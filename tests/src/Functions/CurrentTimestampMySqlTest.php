<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\CurrentTimestamp;

class CurrentTimestampMySqlTest extends CurrentTimestampTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_no_size(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new CurrentTimestamp());
        $this->assertSame('SELECT CURRENT_TIMESTAMP()', $q->toString());
        $result = Arr::first((array)$q->execute()->first());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $result);
    }

    public function test_with_size(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new CurrentTimestamp(6));
        $this->assertSame('SELECT CURRENT_TIMESTAMP(6)', $q->toString());
        $result = Arr::first((array)$q->execute()->first());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d.%d', $result);
    }
}
