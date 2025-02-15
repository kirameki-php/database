<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

class IntColumnBuilder extends ColumnBuilder
{
    /**
     * @return $this
     */
    public function autoIncrement(?int $startFrom = null): static
    {
        $this->definition->autoIncrement = $startFrom ?? $this->getRandomStartValue();
        return $this;
    }

    /**
     * @return int
     */
    protected function getRandomStartValue(): int
    {
        return $this->handler->randomizer->getInt(1_000_000, 9_999_999);
    }
}
