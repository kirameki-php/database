<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Functions\Uuid;

class UuidColumnBuilder extends ColumnBuilder
{
    /**
     * @return $this
     */
    public function generateDefault(): static
    {
        return $this->default(new Uuid());
    }
}
