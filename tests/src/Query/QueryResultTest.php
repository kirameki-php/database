<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;


use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\RawStatement;

class QueryResultTest extends QueryTestCase
{
    public function test_construct(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, 0, ['foo']);
        $this->assertSame($statement, $result->statement);
        $this->assertSame($template, $result->template);
        $this->assertSame($parameters, $result->parameters);
        $this->assertSame(1.1, $result->elapsedMs);
        $this->assertSame(0, $result->getAffectedRowCount());
        $this->assertSame(['foo'], $result->all());
    }

    public function test_instantiate(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, 0, ['foo']);
        $instance = $result->instantiate(['bar']);
        $this->assertSame($result->statement, $instance->statement);
        $this->assertSame($result->template, $instance->template);
        $this->assertSame($result->parameters, $instance->parameters);
        $this->assertSame($result->elapsedMs, $instance->elapsedMs);
        $this->assertSame(['bar'], $instance->all());
    }

    public function test_getAffectedRowCount_using_int(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, 10, ['foo']);
        $this->assertSame(10, $result->getAffectedRowCount());
    }

    public function test_getAffectedRowCount_using_closure(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, fn() => 10, ['foo']);
        $this->assertSame(10, $result->getAffectedRowCount());
    }

    public function test_ensureAffectedRowCount_with_correct_amount(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, fn() => 10, ['foo']);
        $this->assertInstanceOf($result::class, $result->ensureAffectedRowIs(10));
    }

    public function test_ensureAffectedRowCount_with_incorrect_amount(): void
    {
        $template = 'SELECT ?';
        $parameters = [1];
        $statement = new RawStatement($template, $parameters);
        $result = new QueryResult($statement, $template, $parameters, 1.1, fn() => 10, ['foo']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Unexpected affected row count. Expected: 1. Got 10.');
        $this->assertSame(10, $result->ensureAffectedRowIs(1));
    }
}
