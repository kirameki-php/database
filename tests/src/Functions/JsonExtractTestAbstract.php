<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\Functions\JsonExtract;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class JsonExtractTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    protected function createJsonTestTable(Connection $connection): void
    {
        $table = $connection->schema()->createTable('test');
        $table->id();
        $table->json('attrs');
        $table->execute();
    }

    abstract public function test_column_return_value(): void;

    abstract public function test_column_return_object(): void;
}
