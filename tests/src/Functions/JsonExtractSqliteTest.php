<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Functions\JsonExtract;

class JsonExtractSqliteTest extends JsonExtractTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_column_return_value(): void
    {
        $connection = $this->getConnection();
        $this->createJsonTestTable($connection);

        $connection->query()->insertInto('test')
            ->value(['attrs' => '{"users":[1,2,3]}'])
            ->execute();

        $q = $connection->query()
            ->select(JsonExtract::column('attrs', 'users[1]'))
            ->from('test');
        $this->assertSame('SELECT "attrs" -> \'$.users[1]\' FROM "test"', $q->toString());

        $result = Arr::first((array)$q->first());
        $this->assertSame('2', $result);
    }

    public function test_column_return_object(): void
    {
        $connection = $this->getConnection();
        $this->createJsonTestTable($connection);

        $connection->query()->insertInto('test')
            ->value(['attrs' => '{"users":[1,2,3]}'])
            ->execute();

        $q = $connection->query()
            ->select(JsonExtract::column('attrs', 'users', 't'))
            ->from('test');
        $this->assertSame('SELECT "attrs" -> \'$.users\' AS "t" FROM "test"', $q->toString());

        $result = (array)$q->first();
        $this->assertSame(['t' => '[1,2,3]'], $result);
    }
}
