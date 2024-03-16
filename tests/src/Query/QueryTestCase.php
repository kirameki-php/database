<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class QueryTestCase extends DatabaseTestCase
{
    protected function selectBuilder(): SelectBuilder
    {
        return new SelectBuilder($this->mysqlConnection());
    }

    protected function insertBuilder(): InsertBuilder
    {
        return new InsertBuilder($this->mysqlConnection());
    }

    protected function updateBuilder(): UpdateBuilder
    {
        return new UpdateBuilder($this->mysqlConnection());
    }

    protected function deleteBuilder(): DeleteBuilder
    {
        return new DeleteBuilder($this->mysqlConnection());
    }
}
