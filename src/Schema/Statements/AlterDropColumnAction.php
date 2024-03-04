<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterDropColumnAction
{
    public function __construct(
        public readonly string $column,
    )
    {
    }
}
