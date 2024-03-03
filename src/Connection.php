<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Configs\DatabaseConfig;
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
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use Kirameki\Database\Transaction\TransactionHandler;
use Kirameki\Event\EventManager;

class Connection
{
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
    public function __construct(
        protected readonly string $name,
        protected readonly Adapter $adapter,
        protected readonly EventManager $events,
    )
    {
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
        return $this->transactionHandler ??= new TransactionHandler($this, $this->events);
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
        $this->events->emit(new QueryExecuted($this, $result));
        return $result;
    }

    /**
     * @param string $statement
     * @param iterable<array-key, mixed> $bindings
     * @return Result
     */
    public function cursor(string $statement, iterable $bindings = []): Result
    {
        $execution = $this->adapter->cursor($statement, $bindings);
        $result = new Result($this, $execution);
        $this->events->emit(new QueryExecuted($this, $result));
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
}
