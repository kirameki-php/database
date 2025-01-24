<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

class RenameDefinition
{
    /**
     * @param string $from
     * @param string $to
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    )
    {
    }
}
