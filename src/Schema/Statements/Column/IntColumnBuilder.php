<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use function random_int;

class IntColumnBuilder extends ColumnBuilder
{
    /**
     * @return $this
     */
    public function autoIncrement(?int $startFrom = null): static
    {
        $this->definition->autoIncrement = $startFrom ?? random_int(1, 10_000);
        return $this;
    }
}
