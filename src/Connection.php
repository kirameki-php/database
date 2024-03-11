<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\DeleteBuilder;
use Kirameki\Database\Statements\Query\DeleteStatement;
use Kirameki\Database\Statements\Query\InsertBuilder;
use Kirameki\Database\Statements\Query\InsertStatement;
use Kirameki\Database\Statements\Query\SelectBuilder;
use Kirameki\Database\Statements\Query\SelectStatement;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Query\UpdateBuilder;
use Kirameki\Database\Statements\Query\UpdateStatement;
use Kirameki\Database\Statements\Result;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Statement;
use Kirameki\Database\Transaction\TransactionHandler;
use Kirameki\Event\EventManager;

class Connection
{
    /**
     * @var TransactionHandler|null
     */
    protected ?TransactionHandler $transactionHandler;

    /**
     * @param string $name
     * @param DatabaseAdapter $adapter
     * @param EventManager $events
     */
    public function __construct(
        public readonly string $name,
        protected readonly DatabaseAdapter $adapter,
        protected readonly EventManager $events,
    )
    {
    }

    /**
     * @return DatabaseAdapter
     */
    public function getAdapter(): DatabaseAdapter
    {
        return $this->adapter;
    }

    /**
     * @return QuerySyntax
     */
    public function getQuerySyntax(): QuerySyntax
    {
        return $this->adapter->getQuerySyntax();
    }

    /**
     * @return SchemaSyntax
     */
    public function getSchemaSyntax(): SchemaSyntax
    {
        return $this->adapter->getSchemaSyntax();
    }

    /**
     * @return $this
     */
    public function reconnect(): static
    {
        return $this->disconnect()->connect();
    }

    /**
     * @return $this
     */
    public function connect(): static
    {
        $this->adapter->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect(): static
    {
        $this->adapter->disconnect();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->adapter->isConnected();
    }

    /**
     * @param Statement $statement
     * @return Result
     */
    public function query(Statement $statement): Result
    {
        return $this->handleExecution($this->adapter->query($statement));
    }

    /**
     * @param Statement $statement
     * @return Result
     */
    public function cursor(Statement $statement): Result
    {
        return $this->handleExecution($this->adapter->cursor($statement));
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        $statement = new SelectStatement($this->getQuerySyntax());
        $builder = new SelectBuilder($this, $statement);
        return $builder->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        $statement = new InsertStatement($this->getQuerySyntax(), $table);
        return new InsertBuilder($this, $statement);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        $statement = new UpdateStatement($this->getQuerySyntax(), $table);
        return new UpdateBuilder($this, $statement);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        $statement = new DeleteStatement($this->getQuerySyntax(), $table);
        return new DeleteBuilder($this, $statement);
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function transaction(Closure $callback): mixed
    {
        return $this->getTransactionHandler()->run($callback);
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getTransactionHandler()->isActive();
    }

    /**
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        return $this->adapter->tableExists($table);
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->adapter->truncate($table);
    }

    /**
     * @param string $statement
     * @return Execution
     */
    public function applySchema(string $statement): Execution
    {
        $execution = $this->adapter->execute($statement);
        $this->events->emit(new SchemaExecuted($this, $execution));
        return $execution;
    }

    /**
     * @return TransactionHandler
     */
    protected function getTransactionHandler(): TransactionHandler
    {
        return $this->transactionHandler ??= new TransactionHandler($this, $this->events);
    }

    /**
     * @param Execution $execution
     * @return Result
     */
    protected function handleExecution(Execution $execution): Result
    {
        $result = new Result($this, $execution);
        $this->events->emit(new QueryExecuted($this, $result));
        return $result;
    }
}
