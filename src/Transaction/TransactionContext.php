<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Kirameki\Event\EventDispatcher;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\DatabaseConnection;
use Kirameki\Database\Events\TransactionCommitted;
use Kirameki\Database\Events\TransactionCommitting;
use Kirameki\Database\Events\TransactionEvent;
use Kirameki\Database\Events\TransactionRolledBack;
use Kirameki\Event\EventHandler;

class TransactionContext implements TransactionInfo
{
    /**
     * @var int
     */
    public protected(set) int $count = 0;

    /**
     * @var EventHandler<TransactionCommitting>
     */
    public EventHandler $beforeCommit {
        get => $this->events->get(TransactionCommitting::class);
    }

    /**
     * @var EventHandler<TransactionCommitted>
     */
    public EventHandler $afterCommit {
        get => $this->events->get(TransactionCommitted::class);
    }

    /**
     * @var EventHandler<TransactionRolledBack>
     */
    public EventHandler $afterRollback {
        get => $this->events->get(TransactionRolledBack::class);
    }

    /**
     * @param DatabaseConnection $connection
     * @param TransactionOptions|null $options
     */
    public function __construct(
        public protected(set) DatabaseConnection $connection,
        public protected(set) ?TransactionOptions $options = null,
        protected readonly EventDispatcher $events = new EventDispatcher(),
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
        $this->events->emit($event);
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

        if ($level === $currentLevel) {
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
