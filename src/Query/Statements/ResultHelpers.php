<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Collections\Vec;

/**
 * @template TRow of mixed
 */
trait ResultHelpers
{
    /**
     * @return TRow
     */
    public function first(): mixed
    {
        return $this->copy()->limit(1)->execute()->first();
    }

    /**
     * @return TRow|null
     */
    public function firstOrNull(): mixed
    {
        return $this->copy()->limit(1)->execute()->firstOrNull();
    }

    /**
     * @return TRow
     */
    public function single(): mixed
    {
        return $this->copy()->limit(2)->execute()->single();
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        return $this->copy()->limit(1)->execute()->value($column);
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function valueOrNull(string $column): mixed
    {
        return $this->copy()->limit(1)->execute()->valueOrNull($column);
    }

    /**
     * @param string $column
     * @return Vec<mixed>
     */
    public function pluck(string $column): Vec
    {
        return $this->copy()->execute()->pluck($column);
    }
}
