<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class AlterDropColumnAction
{
    public function __construct(
        public readonly string $column,
    )
    {
    }
}
