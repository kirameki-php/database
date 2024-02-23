<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Kirameki\Database\Query\Execution;
use Kirameki\Database\Query\Expressions\Expr;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\ResultLazy;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use Kirameki\Database\Transaction\Transaction;
use Kirameki\Database\Transaction\TransactionHandler;
use Kirameki\Event\EventManager;

class Connection
{
    /**
     * @var string
     */
    protected readonly string $name;

    /**
     * @var Adapter
     */
    protected readonly Adapter $adapter;

    /**
     * @var EventManager
     */
    protected readonly EventManager $events;

    /**
     * @var QueryFormatter|null
     */
    protected ?QueryFormatter $queryFormatter;

    /**
     * @var SchemaFormatter|null
     */
    protected ?SchemaFormatter $schemaFormatter;

    /**
     * @var TransactionHandler|null
     */
    protected ?TransactionHandler $transactionHandler;

    /**
     * @param string $name
     * @param Adapter $adapter
     * @param EventManager $events
     */
    public function __construct(string $name, Adapter $adapter, EventManager $events)
    {
        $this->name = $name;
        $this->adapter = $adapter;
        $this->events = $events;
        $this->transactionHandler = new TransactionHandler($adapter, $events);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return $this->queryFormatter ??= $this->adapter->getQueryFormatter();
    }

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->schemaFormatter ??= $this->adapter->getSchemaFormatter();
    }

    /**
     * @return TransactionHandler
     */
    public function getTransactionHandler(): TransactionHandler
    {
        return $this->transactionHandler ??= new TransactionHandler($this->adapter, $this->events);
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
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Result
     */
    public function query(string $statement, array $bindings = []): Result
    {
        $execution = $this->adapter->query($statement, $bindings);
        $result = new Result($this, $execution);
        $this->events->dispatchClass(QueryExecuted::class, $this, $result);
        return $result;
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return ResultLazy
     */
    public function cursor(string $statement, array $bindings = []): ResultLazy
    {
        $execution = $this->adapter->cursor($statement, $bindings);
        $result = new ResultLazy($this, $execution);
        $this->events->dispatchClass(QueryExecuted::class, $this, $result);
        return $result;
    }

    /**
     * @param string|Expr ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expr ...$columns): SelectBuilder
    {
        return (new SelectBuilder($this))->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return (new InsertBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return (new UpdateBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        return (new DeleteBuilder($this))->table($table);
    }

    /**
     * @param Closure(Transaction): mixed $callback
     * @param bool $useSavepoint
     * @return mixed
     */
    public function transaction(Closure $callback, bool $useSavepoint = false): mixed
    {
        return $this->getTransactionHandler()->run($callback, $useSavepoint);
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getTransactionHandler()->inTransaction();
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
        $this->events->dispatchClass(SchemaExecuted::class, $this, $execution);
        return $execution;
    }
}
