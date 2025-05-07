<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

class WindowBuilderSqliteTest extends WindowBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';
}
