<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\CteAggregate;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Raw;
use Kirameki\Time\Time;
use Tests\Kirameki\Database\Adapters\_Support\IntCastEnum;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function iterator_to_array;

abstract class QueryBuilderTestAbstract extends QueryTestCase
{
    public function test___clone(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->with('User', as: $handler->select()->from('User'))
            ->select()
            ->from('User')
            ->setTag('a', '1');

        $copy = clone $query;

        $this->assertInstanceOf(CteAggregate::class, $copy->statement->with);
        $this->assertInstanceOf(Tags::class, $copy->statement->tags);
        $this->assertNotSame($query->statement->with, $copy->statement->with);
        $this->assertNotSame($query->statement->tags, $copy->statement->tags);
    }

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
        $query->afterQuery(function ($r) use (&$result) { $result[] = $r; });
        $query->afterQuery(function ($r) use (&$result) { $result[] = $r; });
        $this->assertEmpty($result);
        $query->execute();

        $this->assertCount(2, $result);
        foreach ($result as $r) {
            $this->assertInstanceOf(QueryResult::class, $r);
            $this->assertSame([1], $result[0]->pluck('id')->all());
        }
    }

    public function test_cast_to_time_from_string(): void
    {
        $casting = new Raw("'2020-01-01' as c");
        $result = $this->selectBuilder()->columns($casting)->cast('c', Time::class)->execute();
        $value = $result->single()->c;
        $this->assertInstanceOf(Time::class, $value);
        $this->assertSame('2020-01-01 00:00:00', $value->format('Y-m-d H:i:s'));
    }

    public function test_cast_to_int_backed_enum(): void
    {
        $casting = new Raw('2 as c');
        $result = $this->selectBuilder()->columns($casting)->cast('c', IntCastEnum::class)->execute();
        $value = $result->single()->c;
        $this->assertSame(IntCastEnum::B, $value);
        $this->assertSame(2, $value->value);
    }

    public function test_casts_to_different_casts(): void
    {
        $castings = [new Raw("'2020-01-01' as c1"), new Raw('2 as c2')];
        $result = $this->selectBuilder()->columns(...$castings)->casts([
            'c1' => Time::class,
            'c2' => IntCastEnum::class,
        ])->execute();
        $this->assertSame('2020-01-01 00:00:00', $result->single()->c1->format('Y-m-d H:i:s'));
        $this->assertSame(IntCastEnum::B, $result->single()->c2);
    }

    public function test_setTag(): void
    {
        $query = $this->selectBuilder()->from('User')->setTag('a', '1');
        $statement = $query->statement;
        $this->assertNotNull($statement->tags);
        $this->assertSame(['a' => '1'], iterator_to_array($statement->tags));
        $this->assertSame('SELECT * FROM "User" /* a=1 */', $query->toSql());
    }

    public function test_withTags(): void
    {
        $query = $this->selectBuilder()->from('User')->withTags(['a' => '1', 'b' => '2']);
        $statement = $query->statement;
        $this->assertNotNull($statement->tags);
        $this->assertSame(['a' => '1', 'b' => '2'], iterator_to_array($statement->tags));
        $this->assertSame('SELECT * FROM "User" /* a=1,b=2 */', $query->toSql());
    }
}
