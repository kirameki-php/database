<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Events\ConnectionEstablished;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Event\EventManager;
use Random\Randomizer;
use Throwable;

class Connection
{
    /**
     * @param string $name
     * @param Adapter<covariant ConnectionConfig> $adapter
     * @param EventManager|null $events
     * @param QueryHandler|null $queryHandler
     * @param SchemaHandler|null $schemaHandler
     * @param InfoHandler|null $infoHandler
     * @param TransactionContext|null $transactionContext
     */
    public function __construct(
        public readonly string $name,
        public readonly Adapter $adapter,
        protected readonly ?EventManager $events = null,
        protected ?QueryHandler $queryHandler = null,
        protected ?SchemaHandler $schemaHandler = null,
        protected ?InfoHandler $infoHandler = null,
        protected ?TransactionContext $transactionContext = null,
        protected ?Randomizer $randomizer = null,
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
        $this->events?->emit(new ConnectionEstablished($this));
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
        return $this->schemaHandler ??= new SchemaHandler($this, $this->events, $this->randomizer);
    }

    public function info(): InfoHandler
    {
        return $this->infoHandler ??= new InfoHandler($this);
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @param IsolationLevel|null $level
     * @return TReturn
     */
    public function transaction(Closure $callback, ?IsolationLevel $level = null): mixed
    {
        // Already in transaction so just execute callback
        if ($this->inTransaction()) {
            $this->getTransactionContext()->ensureValidIsolationLevel($level);
            return $callback();
        }

        try {
            $this->handleBegin($level);
            $result = $callback();
            $this->handleCommit();
            return $result;
        }
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($throwable);
        }
        finally {
            $this->cleanUpTransaction();
        }
    }

    protected function getTransactionContext(): TransactionContext
    {
        $context = $this->transactionContext;
        if ($context === null) {
            throw new LogicException('No transaction in progress.');
        }
        return $context;
    }

    /**
     * @param IsolationLevel|null $level
     * @return void
     */
    protected function handleBegin(?IsolationLevel $level): void
    {
        $this->connectIfNotConnected();
        $this->adapter->beginTransaction($level);
        $this->events?->emit(new TransactionBegan($this, $level));
        $this->transactionContext = new TransactionContext($this, $level);
    }

    /**
     * @return void
     */
    protected function handleCommit(): void
    {
        $context = $this->getTransactionContext();
        $context->runBeforeCommitCallbacks();
        $this->adapter->commit();
        $this->events?->emit(new TransactionCommitted($this));
        $context->runAfterCommitCallbacks();
    }

    /**
     * @param Throwable $throwable
     * @return never
     */
    protected function rollbackAndThrow(Throwable $throwable): never
    {
        $this->adapter->rollback();
        $this->events?->emit(new TransactionRolledBack($this, $throwable));
        $this->getTransactionContext()->runAfterRollbackCallbacks();
        throw $throwable;
    }

    /**
     * @return void
     */
    protected function cleanUpTransaction(): void
    {
        $this->transactionContext = null;
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->transactionContext !== null;
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function beforeCommit(Closure $callback): static
    {
        $this->getTransactionContext()->beforeCommit($callback);
        return $this;
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function afterCommit(Closure $callback): static
    {
        $this->getTransactionContext()->afterCommit($callback);
        return $this;
    }

    /**
     * @param Closure(): mixed $callback
     * @return $this
     */
    public function afterRollback(Closure $callback): static
    {
        $this->getTransactionContext()->afterRollback($callback);
        return $this;
    }
}
