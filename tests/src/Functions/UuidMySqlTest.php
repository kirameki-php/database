<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\Uuid;

class UuidMySqlTest extends UuidTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_instantiate(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(new Uuid());
        $this->assertSame('SELECT UUID()', $q->toString());

        $result = Arr::first((array)$q->first());
        $this->assertMatchesRegularExpression('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $result);

        $result2 = Arr::first((array)$q->first());
        $this->assertNotSame($result2, $result);
    }
}
