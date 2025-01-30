<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Events\ConnectionEstablished;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionCommitting;
use Kirameki\Database\Events\TransactionEvent;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Transaction\IsolationLevel;
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Database\Transaction\TransactionInfo;
use Kirameki\Event\EventEmitter;
use Random\Randomizer;
use Throwable;

class Connection
{
    /**
     * @param string $name
     * @param Adapter<covariant ConnectionConfig> $adapter
     * @param EventEmitter|null $events
     * @param QueryHandler|null $queryHandler
     * @param SchemaHandler|null $schemaHandler
     * @param InfoHandler|null $infoHandler
     * @param TransactionContext|null $transactionContext
     * @param Tags|null $tags
     * @param Randomizer|null $randomizer
     */
    public function __construct(
        public readonly string $name,
        public readonly Adapter $adapter,
        protected readonly ?EventEmitter $events = null,
        protected ?QueryHandler $queryHandler = null,
        protected ?SchemaHandler $schemaHandler = null,
        protected ?InfoHandler $infoHandler = null,
        protected ?TransactionContext $transactionContext = null,
        protected ?Tags $tags = null,
        protected ?Randomizer $randomizer = null,
    )
    {
    }

    /**
     * @return $this
     */
    public function reconnect(): static
    {
        $this->disconnectIfConnected();
        return $this->connect();
    }

    /**
     * @return $this
     */
    public function connect(): static
    {
        if ($this->isConnected()) {
            throw new LogicException("Connection: \"{$this->name}\" is already established.", [
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
        if (!$this->isConnected()) {
            throw new LogicException("Connection: \"{$this->name}\" is not established.", [
                'name' => $this->name,
            ]);
        }

        $this->adapter->disconnect();
        return $this;
    }

    /**
     * @return bool
     */
    public function disconnectIfConnected(): bool
    {
        if ($this->isConnected()) {
            $this->disconnect();
            return true;
        }
        return false;
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
        return $this->queryHandler ??= new QueryHandler($this, $this->events, $this->getTags());
    }

    /**
     * @return SchemaHandler
     */
    public function schema(): SchemaHandler
    {
        return $this->schemaHandler ??= new SchemaHandler($this, $this->events, $this->randomizer);
    }

    /**
     * @return InfoHandler
     */
    public function info(): InfoHandler
    {
        return $this->infoHandler ??= new InfoHandler($this);
    }

    /**
     * @return Tags
     */
    public function getTags(): Tags
    {
        return $this->tags ??= new Tags();
    }

    /**
     * @template TReturn
     * @param Closure(TransactionInfo): TReturn $callback
     * @param IsolationLevel|null $level
     * @return TReturn
     */
    public function transaction(Closure $callback, ?IsolationLevel $level = null): mixed
    {
        // Already in transaction so just execute callback
        if ($this->inTransaction()) {
            return $this->handleNestedTransaction($callback, $level);
        }

        $context = $this->initTransactionContext($level);
        try {
            $this->handleBegin($context);
            $result = $callback($context);
            $this->handleCommit($context);
            return $result;
        }
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($context, $throwable);
        }
        finally {
            $this->cleanUpTransaction();
        }
    }

    /**
     * @return TransactionInfo
     */
    public function getTransactionInfoOrNull(): ?TransactionInfo
    {
        return $this->transactionContext;
    }

    /**
     * @param IsolationLevel|null $level
     * @return TransactionContext
     */
    protected function initTransactionContext(?IsolationLevel $level): TransactionContext
    {
        return $this->transactionContext = new TransactionContext($this, $level);
    }

    /**
     * @param TransactionContext $context
     * @return void
     */
    protected function handleBegin(TransactionContext $context): void
    {
        $this->connectIfNotConnected();
        $this->adapter->beginTransaction($context->isolationLevel);
        $context->incrementCount();
        $this->emitTransactionEvent($context, new TransactionBegan($context));
    }

    /**
     * @param TransactionContext $context
     * @return void
     */
    protected function handleCommit(TransactionContext $context): void
    {
        $this->emitTransactionEvent($context, new TransactionCommitting($context));
        $this->adapter->commit();
        $this->emitTransactionEvent($context, new TransactionCommitted($this));
    }

    /**
     * @template TReturn
     * @param Closure(TransactionContext): TReturn $callback
     * @param IsolationLevel|null $level
     * @return TReturn
     */
    protected function handleNestedTransaction(Closure $callback, ?IsolationLevel $level): mixed
    {
        $context = $this->transactionContext ?? throw new UnreachableException();
        $context->ensureValidIsolationLevel($level);
        $context->incrementCount();
        $result = $callback($context);
        $context->decrementCount();
        return $result;
    }

    /**
     * @param TransactionContext $context
     * @param Throwable $throwable
     * @return never
     */
    protected function rollbackAndThrow(TransactionContext $context, Throwable $throwable): never
    {
        $this->adapter->rollback();
        $this->emitTransactionEvent($context, new TransactionRolledBack($this, $throwable));
        throw $throwable;
    }

    /**
     * @return void
     */
    protected function cleanUpTransaction(): void
    {
        $this->transactionContext?->decrementCount();
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
     * @param TransactionContext $context
     * @param TransactionEvent $event
     * @return void
     */
    protected function emitTransactionEvent(TransactionContext $context, TransactionEvent $event): void
    {
        $context->emitTransactionEvent($event);
        $this->events?->emit($event);
    }
}
