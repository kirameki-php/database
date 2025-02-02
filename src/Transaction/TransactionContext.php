<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionCommitting;
use Kirameki\Database\Events\TransactionEvent;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Event\EventHandler;
use Kirameki\Event\HandlesEvents;

class TransactionContext implements TransactionInfo
{
    use HandlesEvents;

    /**
     * @var int
     */
    public protected(set) int $count = 0 {
        get => $this->count;
        set => $this->count = $value;
    }

    /**
     * @var EventHandler<TransactionCommitting>
     */
    public EventHandler $beforeCommit {
        get => $this->resolveEventHandler(TransactionCommitting::class);
    }

    /**
     * @var EventHandler<TransactionCommitted>
     */
    public EventHandler $afterCommit {
        get => $this->resolveEventHandler(TransactionCommitted::class);
    }

    /**
     * @var EventHandler<TransactionRolledBack>
     */
    public EventHandler $afterRollback {
        get => $this->resolveEventHandler(TransactionRolledBack::class);
    }

    /**
     * @param Connection $connection
     * @param TransactionOptions|null $options
     */
    public function __construct(
        public protected(set) Connection $connection,
        public protected(set) ?TransactionOptions $options = null,
    )
    {
    }

    /**
     * @internal
     * @return int
     */
    public function incrementCount(): int
    {
        return ++$this->count;
    }

    /**
     * @internal
     * @return int
     */
    public function decrementCount(): int
    {
        return --$this->count;
    }

    /**
     * @internal
     * @param TransactionEvent $event
     * @return void
     */
    public function emitTransactionEvent(TransactionEvent $event): void
    {
        $this->emitEvent($event);
    }

    /**
     * @internal
     * @param IsolationLevel|null $level
     * @return $this
     */
    public function ensureValidIsolationLevel(?IsolationLevel $level): static
    {
        if ($level === null) {
            return $this;
        }

        $currentLevel = $this->options?->isolationLevel;

        if($level === $currentLevel) {
            return $this;
        }

        $currentName = $currentLevel->name ?? 'null';
        $givenName = $level->name ?? 'null';

        throw new LogicException("Transaction isolation level mismatch. Expected: {$currentName}. Got: {$givenName}", [
            'connection' => $this->connection->name,
            'current' => $currentLevel,
            'given' => $level,
        ]);
    }
}
