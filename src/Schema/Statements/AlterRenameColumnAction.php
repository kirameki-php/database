<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterRenameColumnAction
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    )
    {
    }
}
