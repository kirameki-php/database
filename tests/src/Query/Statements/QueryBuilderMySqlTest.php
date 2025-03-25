<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\CteAggregate;
use Kirameki\Database\Query\Statements\Tags;
use function iterator_to_array;

final class QueryBuilderMySqlTest extends QueryBuilderTestAbstract
{
    protected string $useConnection = 'mysql';

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

    public function test_setTag(): void
    {
        $query = $this->selectBuilder()->from('User')->setTag('a', '1');
        $statement = $query->statement;
        $this->assertNotNull($statement->tags);
        $this->assertSame(['a' => '1'], iterator_to_array($statement->tags));
        $this->assertSame('SELECT * FROM `User` /* a=1 */', $query->toSql());
    }

    public function test_withTags(): void
    {
        $query = $this->selectBuilder()->from('User')->withTags(['a' => '1', 'b' => '2']);
        $statement = $query->statement;
        $this->assertNotNull($statement->tags);
        $this->assertSame(['a' => '1', 'b' => '2'], iterator_to_array($statement->tags));
        $this->assertSame('SELECT * FROM `User` /* a=1,b=2 */', $query->toSql());
    }

}
