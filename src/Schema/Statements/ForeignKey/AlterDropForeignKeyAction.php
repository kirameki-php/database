<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\ForeignKey;

class AlterDropForeignKeyAction
{
    public function __construct(
        public readonly string $name,
    )
    {
    }
}
