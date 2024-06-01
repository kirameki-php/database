<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Kirameki\Event\EventManager;
use Throwable;

class TransactionHandler
{
    /**
     * @var TransactionContext|null
     */
    protected ?TransactionContext $context = null;

    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        protected readonly Connection $connection,
        protected readonly EventManager $events,
    )
    {
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @param IsolationLevel|null $level
     * @return TReturn
     */
    public function run(Closure $callback, ?IsolationLevel $level = null): mixed
    {
        // Already in transaction so just execute callback
        if ($this->isActive()) {
            $this->ensureValidIsolationLevel($level);
            return $callback();
        }

        try {
            $this->handleBegin($level);
            $result = $callback();
            $this->handleCommit();
            return $result;
        }
        // We will propagate up to the first transaction block, rollback and then rethrow.
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($throwable);
        }
        finally {
            $this->context = null;
        }
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->context !== null;
    }

    public function getContext(): TransactionContext
    {
        $context = $this->context;
        if ($context === null) {
            throw new LogicException('No transaction in progress.');
        }
        return $context;
    }

    /**
     * @return IsolationLevel|null
     */
    public function getIsolationLevel(): ?IsolationLevel
    {
        return $this->context?->isolationLevel;
    }

    /**
     * @param IsolationLevel|null $level
     * @return void
     */
    protected function handleBegin(?IsolationLevel $level): void
    {
        $connection = $this->connection;
        $connection->connectIfNotConnected();
        $connection->adapter->beginTransaction($level);
        $this->events->emit(new TransactionBegan($connection, $level));
        $this->context = new TransactionContext($level);
    }

    /**
     * @return void
     */
    protected function handleCommit(): void
    {
        $connection = $this->connection;
        $context = $this->getContext();
        $context->runBeforeCommitCallbacks();
        $connection->adapter->commit();
        $this->events->emit(new TransactionCommitted($connection));
        $context->runAfterCommitCallbacks();
    }

    /**
     * @param Throwable $throwable
     * @return never
     */
    protected function rollbackAndThrow(Throwable $throwable): never
    {
        $connection = $this->connection;
        $connection->adapter->rollback();
        $this->events->emit(new TransactionRolledBack($connection, $throwable));
        $this->getContext()->runAfterRollbackCallbacks();
        throw $throwable;
    }

    /**
     * @param IsolationLevel|null $level
     * @return void|never
     */
    protected function ensureValidIsolationLevel(?IsolationLevel $level): void
    {
        if ($level === null) {
            return;
        }

        $context = $this->getContext();

        if($level === $context->isolationLevel) {
            return;
        }

        throw new LogicException('Cannot change Isolation level within the same transaction.', [
            'connection' => $this->connection->name,
            'current' => $context->isolationLevel,
            'given' => $level,
        ]);
    }
}
