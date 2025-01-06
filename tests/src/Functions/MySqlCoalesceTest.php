<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\Coalesce;

class MySqlCoalesceTest extends CoalesceTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_values_construct(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(Coalesce::values('NULL', '1', '1.1'));
        $this->assertSame('SELECT COALESCE(NULL, 1, 1.1)', $q->toString());

        $result = Arr::first((array)$q->execute()->first());
        $this->assertSame('1.0', $result);
    }

    public function test_columns_construct(): void
    {
        $connection = $this->getConnection();

        $q = $connection->query()->select(Coalesce::columns('a', 'b'));
        $this->assertSame('SELECT COALESCE(`a`, `b`)', $q->toString());
    }
}
