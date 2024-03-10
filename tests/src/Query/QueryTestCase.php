<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Statements\Query\DeleteBuilder;
use Kirameki\Database\Statements\Query\InsertBuilder;
use Kirameki\Database\Statements\Query\SelectBuilder;
use Kirameki\Database\Statements\Query\UpdateBuilder;
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
