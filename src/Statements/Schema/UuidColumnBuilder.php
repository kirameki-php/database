<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Expressions\Uuid;

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
