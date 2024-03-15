<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Event\EventManager;
use Throwable;

class TransactionHandler
{
    /**
     * @var bool
     */
    protected bool $active = false;

    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        protected Connection $connection,
        protected EventManager $events,
    )
    {
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function run(Closure $callback): mixed
    {
        // Already in transaction so just execute callback
        if ($this->isActive()) {
            return $callback();
        }

        try {
            $this->handleBegin();
            $result = $callback();
            $this->handleCommit();
            return $result;
        }
        // We will propagate up to the first transaction block, rollback and then rethrow.
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($throwable);
        }
        finally {
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
     * @return void
     */
    protected function handleBegin(): void
    {
        $this->connection->adapter->beginTransaction();
        $this->events->emit(new TransactionBegan($this->connection));
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
