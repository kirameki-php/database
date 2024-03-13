<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\DeleteBuilder;
use Kirameki\Database\Statements\Query\DeleteStatement;
use Kirameki\Database\Statements\Query\InsertBuilder;
use Kirameki\Database\Statements\Query\InsertStatement;
use Kirameki\Database\Statements\Query\QueryExecution;
use Kirameki\Database\Statements\Query\QueryStatement;
use Kirameki\Database\Statements\Query\QueryResult;
use Kirameki\Database\Statements\Query\SelectBuilder;
use Kirameki\Database\Statements\Query\SelectStatement;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Query\UpdateBuilder;
use Kirameki\Database\Statements\Query\UpdateStatement;
use Kirameki\Event\EventManager;

readonly class QueryHandler
{
    /**
     * @var DatabaseAdapter
     */
    protected DatabaseAdapter $adapter;

    /**
     * @var QuerySyntax
     */
    protected QuerySyntax $syntax;

    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        protected Connection $connection,
        protected EventManager $events,
    )
    {
        $this->adapter = $connection->getAdapter();
        $this->syntax = $this->adapter->getQuerySyntax();
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        $statement = new SelectStatement($this->syntax);
        $builder = new SelectBuilder($this, $statement);
        return $builder->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        $statement = new InsertStatement($this->syntax, $table);
        return new InsertBuilder($this, $statement);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        $statement = new UpdateStatement($this->syntax, $table);
        return new UpdateBuilder($this, $statement);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        $statement = new DeleteStatement($this->syntax, $table);
        return new DeleteBuilder($this, $statement);
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function execute(QueryStatement $statement): QueryResult
    {
        return $this->handleExecution($this->adapter->query($statement));
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        return $this->handleExecution($this->adapter->cursor($statement));
    }

    /**
     * @template TStatement of QueryStatement
     * @param QueryExecution<TStatement> $execution
     * @return QueryResult<TStatement>
     */
    protected function handleExecution(Execution $execution): QueryResult
    {
        $result = new QueryResult($this->connection, $execution);
        $this->events->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }
}
