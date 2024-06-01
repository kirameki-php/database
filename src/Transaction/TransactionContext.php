<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Database\Transaction\Support\IsolationLevel;

class TransactionContext
{
    /**
     * @param IsolationLevel|null $isolationLevel
     * @param list<Closure(): mixed>|null $beforeCommitCallbacks
     * @param list<Closure(): mixed>|null $afterCommitCallbacks
     * @param list<Closure(): mixed>|null $afterRollbackCallbacks
     */
    public function __construct(
        public readonly ?IsolationLevel $isolationLevel,
        protected ?array $beforeCommitCallbacks = null,
        protected ?array $afterCommitCallbacks = null,
        protected ?array $afterRollbackCallbacks = null,
    )
    {
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    public function beforeCommit(Closure $callback): void
    {
        $this->beforeCommitCallbacks ??= [];
        $this->beforeCommitCallbacks[] = $callback;
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    public function afterCommit(Closure $callback): void
    {
        $this->afterCommitCallbacks ??= [];
        $this->afterCommitCallbacks[] = $callback;
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    public function afterRollback(Closure $callback): void
    {
        $this->afterRollbackCallbacks ??= [];
        $this->afterRollbackCallbacks[] = $callback;
    }

    /**
     * @return void
     */
    public function runBeforeCommitCallbacks(): void
    {
        $this->runCallbacks($this->beforeCommitCallbacks);
    }

    /**
     * @return void
     */
    public function runAfterCommitCallbacks(): void
    {
        $this->runCallbacks($this->afterCommitCallbacks);
    }

    /**
     * @return void
     */
    public function runAfterRollbackCallbacks(): void
    {
        $this->runCallbacks($this->afterRollbackCallbacks);
    }

    /**
     * @param list<Closure(): mixed>|null $callbacks
     * @return void
     */
    protected function runCallbacks(?array $callbacks): void
    {
        if ($callbacks === null) {
            return;
        }

        foreach ($callbacks as $callback) {
            $callback();
        }
    }
}
