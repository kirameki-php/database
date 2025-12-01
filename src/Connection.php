<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Exceptions\LogicException;
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
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Database\Transaction\TransactionInfo;
use Kirameki\Database\Transaction\TransactionOptions;
use Kirameki\Event\EventEmitter;
use Random\Randomizer;
use Throwable;

class Connection
{
    /**
     * @var QueryHandler|null
     */
    protected ?QueryHandler $queryHandler = null;

    /**
     * @var SchemaHandler|null
     */
    protected ?SchemaHandler $schemaHandler = null;

    /**
     * @var InfoHandler|null
     */
    protected ?InfoHandler $infoHandler = null;

    /**
     * @var TransactionContext|null
     */
    protected ?TransactionContext $transactionContext = null;

    /**
     * @var Tags
     */
    public Tags $tags {
        get => $this->tags ??= new Tags();
    }

    /**
     * @var TransactionInfo|null
     */
    public ?TransactionInfo $transactionInfo {
        get => $this->transactionContext;
    }

    /**
     * @param string $name
     * @param Adapter<covariant ConnectionConfig> $adapter
     * @param EventEmitter|null $events
     * @param Randomizer|null $randomizer
     */
    public function __construct(
        public readonly string $name,
        public readonly Adapter $adapter,
        protected readonly ?EventEmitter $events = null,
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
        return $this->queryHandler ??= new QueryHandler($this, $this->events);
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
     * @template TReturn
     * @param Closure(TransactionInfo): TReturn $callback
     * @param TransactionOptions|null $options
     * @return TReturn
     */
    public function transaction(Closure $callback, ?TransactionOptions $options = null): mixed
    {
        $context = $this->initTransactionContext($options);
        try {
            $this->handleBegin($context);
            $result = $callback($context);
            $this->handleCommit($context);
            return $result;
        } catch (Throwable $throwable) {
            $this->rollbackAndThrow($context, $throwable);
        } finally {
            $this->cleanUpTransaction($context);
        }
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->transactionContext !== null;
    }

    /**
     * @param TransactionOptions|null $options
     * @return TransactionContext
     */
    protected function initTransactionContext(?TransactionOptions $options): TransactionContext
    {
        $context = $this->transactionContext ??= new TransactionContext($this, $options);

        if ($context->count > 0) {
            $context->ensureValidIsolationLevel($options?->isolationLevel);
        }

        return $context;
    }

    /**
     * @param TransactionContext $context
     * @return void
     */
    protected function handleBegin(TransactionContext $context): void
    {
        if ($context->count === 0) {
            $this->connectIfNotConnected();
            $this->adapter->beginTransaction($context->options);
            $context->incrementCount();
            $this->emitTransactionEvent($context, new TransactionBegan($context));
        } else {
            $context->incrementCount();
        }
    }

    /**
     * @param TransactionContext $context
     * @return void
     */
    protected function handleCommit(TransactionContext $context): void
    {
        if ($context->count === 1) {
            $this->emitTransactionEvent($context, new TransactionCommitting($context));
            $this->adapter->commit();
            $context->decrementCount();
            $this->emitTransactionEvent($context, new TransactionCommitted($this));
        } else {
            $context->decrementCount();
        }
    }

    /**
     * @param TransactionContext $context
     * @param Throwable $throwable
     * @return never
     */
    protected function rollbackAndThrow(TransactionContext $context, Throwable $throwable): never
    {
        if ($context->count === 1) {
            $this->adapter->rollback();
            $context->decrementCount();
            $this->emitTransactionEvent($context, new TransactionRolledBack($this, $throwable));
        } else {
            $context->decrementCount();
        }
        throw $throwable;
    }

    /**
     * @param TransactionContext $context
     * @return void
     */
    protected function cleanUpTransaction(TransactionContext $context): void
    {
        if ($context->count === 0) {
            $this->transactionContext = null;
        }
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
