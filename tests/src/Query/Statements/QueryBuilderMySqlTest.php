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
}
