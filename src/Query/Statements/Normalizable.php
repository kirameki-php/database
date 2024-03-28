<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Iterator;

interface Normalizable
{
    /**
     * @param iterable<int, mixed> $rows
     * @return Iterator<int, mixed>
     */
    public function normalize(iterable $rows): Iterator;
}
