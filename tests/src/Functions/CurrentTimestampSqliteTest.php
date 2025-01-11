<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\CurrentTimestamp;

class CurrentTimestampSqliteTest extends CurrentTimestampTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_no_size(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new CurrentTimestamp());
        $this->assertSame('SELECT DATETIME("now", "localtime")', $q->toString());
        $result = Arr::first((array)$q->execute()->first());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $result);
    }

    public function test_with_size(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new CurrentTimestamp(6));
        $this->assertSame('SELECT STRFTIME("%Y-%m-%d %H:%M:%f", DATETIME("now", "localtime"))', $q->toString());
        $result = Arr::first((array)$q->execute()->first());
        $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d.%d', $result);
    }
}
