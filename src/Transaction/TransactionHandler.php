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
     * @var bool
     */
    protected bool $active = false;

    /**
     * @var IsolationLevel|null
     */
    protected ?IsolationLevel $isolationLevel;

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
            if ($level !== null) {
                throw new LogicException('Transaction: Cannot set isolation level in nested transactions.');
            }
            return $callback();
        }

        try {
            $this->isolationLevel = $level;
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
            $this->isolationLevel = null;
            $this->active = false;
        }
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return IsolationLevel|null
     */
    public function getIsolationLevel(): ?IsolationLevel
    {
        return $this->isolationLevel;
    }

    /**
     * @param IsolationLevel|null $level
     * @return void
     */
    protected function handleBegin(?IsolationLevel $level): void
    {
        $this->connection->connectIfNotConnected();
        $this->connection->adapter->beginTransaction($level);
        $this->events->emit(new TransactionBegan($this->connection, $level));
    }

    /**
     * @return void
     */
    protected function handleCommit(): void
    {
        $this->connection->adapter->commit();
        $this->events->emit(new TransactionCommitted($this->connection));
    }

    /**
     * @param Throwable $throwable
     * @return never
     */
    protected function rollbackAndThrow(Throwable $throwable): never
    {
        $this->connection->adapter->rollback();
        $this->events->emit(new TransactionRolledBack($this->connection, $throwable));
        throw $throwable;
    }
}
