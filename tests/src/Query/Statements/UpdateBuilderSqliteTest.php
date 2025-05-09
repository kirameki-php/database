<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

class UpdateBuilderSqliteTest extends UpdateBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';
}
