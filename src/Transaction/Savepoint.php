<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

class Savepoint extends Transaction
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        throw new SavepointRollback($this->id, 'Rollback to Savepoint:' . $this->id);
    }
}
