<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\Uuid;

class UuidSqliteTest extends UuidTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_instantiate(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new Uuid());
        $this->assertSame('SELECT PRINTF(\'%s-%s-%s-%s-%s\', LOWER(HEX(RANDOMBLOB(4))), LOWER(HEX(RANDOMBLOB(2))), LOWER(HEX(RANDOMBLOB(2))), LOWER(HEX(RANDOMBLOB(2))), LOWER(HEX(RANDOMBLOB(6))))', $q->toSql());

        $result = Arr::first((array)$q->first());
        $this->assertMatchesRegularExpression('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $result);

        $result2 = Arr::first((array)$q->first());
        $this->assertNotSame($result2, $result);
    }
}
