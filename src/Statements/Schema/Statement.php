<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

abstract class Statement
{
    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }
}
