<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\QueryBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class QueryTestCase extends DatabaseTestCase
{
    protected string $useConnection;

    protected function connect(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    protected function selectBuilder(): SelectBuilder
    {
        return new SelectBuilder($this->connect()->query());
    }

    protected function insertBuilder(string $table): InsertBuilder
    {
        return new InsertBuilder($this->connect()->query(), $table);
    }

    protected function updateBuilder(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this->connect()->query(), $table);
    }

    protected function deleteBuilder(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this->connect()->query(), $table);
    }

    protected function assertSameSql(string $expected, QueryBuilder $query): void
    {
        $expected = match ($this->useConnection) {
            'mysql' => str_replace('`', '"', $expected),
            default => $expected,
        };
        $this->assertSame($expected, $query->toSql());
    }
}
