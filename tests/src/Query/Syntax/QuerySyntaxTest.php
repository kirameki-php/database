<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\TagsFormat;
use Tests\Kirameki\Database\Query\QueryTestCase;

class QuerySyntaxTest extends QueryTestCase
{
    public function test_interpolate_basic(): void
    {
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1]);
        $handler = $this->sqliteConnection()->query();
        $this->assertSame('SELECT * FROM `a` WHERE id = 1', $handler->toString($statement));
    }

    public function test_interpolate_with_escape_character(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'??\' AND id = ?', ['a']);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??\' AND id = \'a\'', $handler->toString($statement));
    }

    public function test_interpolate_with_escape_character_and_parameter(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'???\'', [1]);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??1\'', $handler->toString($statement));
    }

    public function test_interpolate_too_many_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: 1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1, 2]);
        $handler->toString($statement);
    }

    public function test_interpolate_not_enough_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: -1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?');
        $handler->toString($statement);
    }

    public function test_formatTags_as_log(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=1 */', $q->toString());
    }

    public function test_formatTags_as_open_telemetry(): void
    {
        $config = new DatabaseConfig([], tagsFormat: TagsFormat::OpenTelemetry);
        $adapter = new SqliteAdapter($config, new SqliteConfig(':memory:'));
        $handler = $this->createTempConnection('sqlite', $adapter)->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=\'1\' */', $q->toString());
    }
}
