<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Events\TransactionBegan;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Database\Events\TransactionRolledBackToSavepoint;
use Kirameki\Database\Events\TransactionSaved;
use Kirameki\Event\EventManager;
use RuntimeException;
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
     * @param bool $useSavepoint
     * @return mixed
     */
    public function run(Closure $callback, bool $useSavepoint = false): mixed
    {
        try {
            // Actual transaction
            if (!$this->inTransaction()) {
                return $this->runInTransaction($callback);
            }
            // Savepoint if already in transaction and flag is set
            if ($useSavepoint) {
                return $this->runInSavepoint($callback);
            }
            // Already in transaction so just execute callback
            return $callback($this->txStack[-1]);
        }
        // This is thrown when user calls rollback() on Savepoint instance.
        catch (SavepointRollback $rollback) {
            $this->rollbackToSavepoint($rollback);
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

        $this->events->dispatchClass(TransactionBegan::class, $tx);

        $result = $callback($tx);

        $this->adapter->commit();

        $this->events->dispatchClass(TransactionCommitted::class);

        return $result;
    }

    /**
     * @param Closure(Transaction): mixed $callback
     * @return mixed
     */
    protected function runInSavepoint(Closure $callback): mixed
    {
        $savepointId = (string)(count($this->txStack) + 1);

        $tx = $this->txStack[] = new Savepoint($savepointId);

        $this->adapter->setSavepoint($savepointId);

        $this->events->dispatchClass(TransactionSaved::class, $tx);

        return $callback($tx);
    }

    /**
     * @param SavepointRollback $rollback
     */
    protected function rollbackToSavepoint(SavepointRollback $rollback): void
    {
        $this->adapter->rollbackSavepoint($rollback->id);

        $this->events->dispatchClass(TransactionRolledBackToSavepoint::class, $rollback);

        while($tx = array_pop($this->txStack)) {
            if ($tx instanceof Savepoint && $tx->id === $rollback->id) {
                return;
            }
        }

        throw new RuntimeException("Invalid Savepoint: $rollback->id");
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
