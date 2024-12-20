<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Query\Statements\Delete\DeleteBuilder;
use Kirameki\Database\Query\Statements\Insert\InsertBuilder;
use Kirameki\Database\Query\Statements\Select\SelectBuilder;
use Kirameki\Database\Query\Statements\Update\UpdateBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class QueryTestCase extends DatabaseTestCase
{
    protected function selectBuilder(): SelectBuilder
    {
        return new SelectBuilder($this->mysqlConnection()->query());
    }

    protected function insertBuilder(string $table): InsertBuilder
    {
        return new InsertBuilder($this->mysqlConnection()->query(), $table);
    }

    protected function updateBuilder(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this->mysqlConnection()->query(), $table);
    }

    protected function deleteBuilder(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this->mysqlConnection()->query(), $table);
    }
}
