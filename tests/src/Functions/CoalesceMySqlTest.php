<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\Coalesce;

class CoalesceMySqlTest extends CoalesceTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_values_construct(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(Coalesce::values('NULL', '1', '1.1'));
        $this->assertSame('SELECT COALESCE(NULL, 1, 1.1)', $q->toSql());

        $result = Arr::first((array)$q->first());
        $this->assertSame('1.0', $result);
    }
}
