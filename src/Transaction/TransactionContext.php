<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Connection;

class TransactionContext implements TransactionInfo
{
    public protected(set) Connection $connection;
    public protected(set) ?IsolationLevel $isolationLevel;

    /**
     * @param Connection $connection
     * @param IsolationLevel|null $isolationLevel
     * @param list<Closure(): mixed>|null $beforeCommitCallbacks
     * @param list<Closure(): mixed>|null $afterCommitCallbacks
     * @param list<Closure(): mixed>|null $afterRollbackCallbacks
     */
    public function __construct(
        Connection $connection,
        ?IsolationLevel $isolationLevel,
        protected ?array $beforeCommitCallbacks = null,
        protected ?array $afterCommitCallbacks = null,
        protected ?array $afterRollbackCallbacks = null,
    )
    {
        $this->connection = $connection;
        $this->isolationLevel = $isolationLevel;
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
     * @internal
     * @return void
     */
    public function runBeforeCommitCallbacks(): void
    {
        $this->runCallbacks($this->beforeCommitCallbacks);
    }

    /**
     * @internal
     * @return void
     */
    public function runAfterCommitCallbacks(): void
    {
        $this->runCallbacks($this->afterCommitCallbacks);
    }

    /**
     * @internal
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

    /**
     * @internal
     * @param IsolationLevel|null $level
     * @return void|never
     */
    public function ensureValidIsolationLevel(?IsolationLevel $level): void
    {
        if ($level === null) {
            return;
        }

        if($level === $this->isolationLevel) {
            return;
        }

        throw new LogicException('Cannot change Isolation level within the same transaction.', [
            'connection' => $this->connection->name,
            'current' => $this->isolationLevel,
            'given' => $level,
        ]);
    }
}
