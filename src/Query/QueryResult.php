<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Kirameki\Collections\Vec;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Query\Statements\QueryStatement as QueryStatement;

/**
 * @template TQueryStatement of QueryStatement
 * @template TRow of mixed
 * @extends Vec<TRow>
 */
class QueryResult extends Vec
{
    /**
     * @param TQueryStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     * @param float $elapsedMs
     * @param int $affectedRowCount
     * @param iterable<int, TRow> $rows
     */
    public function __construct(
        public readonly QueryStatement $statement,
        public readonly string $template,
        public readonly array $parameters,
        public readonly float $elapsedMs,
        public readonly int $affectedRowCount,
        iterable $rows,
    )
    {
        parent::__construct($rows);
    }

    /**
     * @inheritDoc
     */
    public function instantiate(mixed $iterable): static
    {
        return new static(
            $this->statement,
            $this->template,
            $this->parameters,
            $this->elapsedMs,
            $this->affectedRowCount,
            $iterable,
        );
    }

    /**
     * @param int $expected
     * @return $this
     */
    public function ensureAffectedRowIs(int $expected): static
    {
        $affectedRows = $this->affectedRowCount;
        if ($affectedRows !== $expected) {
            $message = "Unexpected affected row count. Expected: {$expected}. Got: {$affectedRows}.";
            throw new QueryException($message, $this->statement, [
                'result' => $this,
                'expected' => $expected,
                'actual' => $affectedRows,
            ]);
        }
        return $this;
    }
}
