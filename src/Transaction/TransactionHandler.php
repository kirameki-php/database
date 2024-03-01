<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Event\EventManager;
use Throwable;

class TransactionHandler
{
    /**
     * @param Adapter $adapter
     * @param EventManager $events
     * @param array<Transaction> $txStack
     */
    public function __construct(
        protected Adapter $adapter,
        protected EventManager $events,
        protected array $txStack = [],
    )
    {
    }

    /**
     * @param Closure(Transaction): mixed $callback
     * @return mixed
     */
    public function run(Closure $callback): mixed
    {
        try {
            // Actual transaction
            if (!$this->inTransaction()) {
                return $this->runInTransaction($callback);
            }
            // Already in transaction so just execute callback
            return $callback($this->txStack[-1]);
        }
        // This is thrown when user calls rollback() on Transaction instances.
        // We will propagate up to the first transaction block and do a rollback there.
        catch (Rollback $rollback) {
            $this->rollback($rollback);
        }
        // We will propagate up to the first transaction block, rollback and then rethrow.
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($throwable);
        }
        return null;
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return count($this->txStack) > 0;
    }

    /**
     * @param Closure(Transaction): mixed $callback
     * @return mixed
     */
    protected function runInTransaction(Closure $callback): mixed
    {
        $tx = $this->txStack[] = new Transaction();

        $this->adapter->beginTransaction();

        $this->events->emit(new TransactionBegan($tx));

        $result = $callback($tx);

        $this->adapter->commit();

        $this->events->dispatchClass(TransactionCommitted::class);

        return $result;
    }

    /**
     * @param Rollback $rollback
     */
    protected function rollback(Rollback $rollback): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->adapter->rollback();
            $this->events->dispatchClass(TransactionRolledBack::class, $rollback);
            return;
        }

        throw $rollback;
    }

    /**
     * @param Throwable $throwable
     */
    protected function rollbackAndThrow(Throwable $throwable): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->adapter->rollback();
            $this->events->dispatchClass(TransactionRolledBack::class, $throwable);
        }

        throw $throwable;
    }
}
