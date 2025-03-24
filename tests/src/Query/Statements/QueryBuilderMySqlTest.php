<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryResult;
use Tests\Kirameki\Database\Query\QueryTestCase;

final class QueryBuilderMySqlTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_afterQuery(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $handler = $conn->query();
        $handler->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();

        $query = $handler->select()->from('User')->where('id', 1);
        $result = [];
        $query->afterQuery(function ($r) use (&$result) {
            $result[] = $r;
        });
        $query->afterQuery(function ($r) use (&$result) {
            $result[] = $r;
        });
        $this->assertEmpty($result);
        $query->execute();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(QueryResult::class, $result[0]);
        $this->assertSame([1], $result[0]->pluck('id')->all());
    }
}
