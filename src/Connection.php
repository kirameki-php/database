<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Events\ConnectionEstablished;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Kirameki\Database\Transaction\TransactionHandler;
use Kirameki\Event\EventManager;

class Connection
{
    /**
     * @param string $name
     * @param Adapter<ConnectionConfig> $adapter
     * @param EventManager $events
     * @param QueryHandler|null $queryHandler
     * @param SchemaHandler|null $schemaHandler
     * @param InfoHandler|null $infoHandler
     * @param TransactionHandler|null $transactionHandler
     */
    public function __construct(
        public readonly string $name,
        public readonly Adapter $adapter,
        protected readonly EventManager $events,
        protected ?QueryHandler $queryHandler = null,
        protected ?SchemaHandler $schemaHandler = null,
        protected ?InfoHandler $infoHandler = null,
        protected ?TransactionHandler $transactionHandler = null,
    )
    {
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
        if ($this->isConnected()) {
            throw new LogicException("Connection: {$this->name} is already established.", [
                'name' => $this->name,
            ]);
        }
        $this->adapter->connect();
        $this->events->emit(new ConnectionEstablished($this));
        return $this;
    }

    /**
     * Returns **true** if the connection was established, **false** if it was already connected.
     *
     * @return bool
     */
    public function connectIfNotConnected(): bool
    {
        if (!$this->isConnected()) {
            $this->connect();
            return true;
        }
        return false;
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
     * @return QueryHandler
     */
    public function query(): QueryHandler
    {
        return $this->queryHandler ??= new QueryHandler($this, $this->events);
    }

    /**
     * @return SchemaHandler
     */
    public function schema(): SchemaHandler
    {
        return $this->schemaHandler ??= new SchemaHandler($this, $this->events);
    }

    public function info(): InfoHandler
    {
        return $this->infoHandler ??= new InfoHandler($this);
    }

    /**
     * @return TransactionHandler
     */
    protected function getTransactionHandler(): TransactionHandler
    {
        return $this->transactionHandler ??= new TransactionHandler($this, $this->events);
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @param IsolationLevel|null $level
     * @return TReturn
     */
    public function transaction(Closure $callback, ?IsolationLevel $level = null): mixed
    {
        return $this->getTransactionHandler()->run($callback, $level);
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getTransactionHandler()->isActive();
    }

    /**
     * @return IsolationLevel
     */
    public function getTransactionIsolationLevel(): IsolationLevel
    {
        return $this->getTransactionHandler()->getIsolationLevel()
            ?? $this->adapter->getConnectionConfig()->getIsolationLevel();
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function beforeCommit(Closure $callback): static
    {
        $this->getTransactionHandler()->getContext()->beforeCommit($callback);
        return $this;
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function afterCommit(Closure $callback): static
    {
        $this->getTransactionHandler()->getContext()->afterCommit($callback);
        return $this;
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function afterRollback(Closure $callback): static
    {
        $this->getTransactionHandler()->getContext()->afterRollback($callback);
        return $this;
    }
}
