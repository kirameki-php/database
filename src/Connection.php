<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Transaction\TransactionHandler;
use Kirameki\Event\EventManager;

class Connection
{
    /**
     * @param string $name
     * @param DatabaseAdapter $adapter
     * @param EventManager $events
     * @param QueryHandler|null $queryHandler
     * @param SchemaHandler|null $schemaHandler
     * @param TransactionHandler|null $transactionHandler
     */
    public function __construct(
        public readonly string $name,
        protected readonly DatabaseAdapter $adapter,
        protected readonly EventManager $events,
        protected ?QueryHandler $queryHandler = null,
        protected ?SchemaHandler $schemaHandler = null,
        protected ?TransactionHandler $transactionHandler = null,
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
}
