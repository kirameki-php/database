<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
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
