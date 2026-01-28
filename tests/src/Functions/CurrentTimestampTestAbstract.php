<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Database\DatabaseConnection;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class CurrentTimestampTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): DatabaseConnection
    {
        return $this->createTempConnection($this->useConnection);
    }

    abstract public function test_no_size(): void;

    abstract public function test_with_size(): void;
}
