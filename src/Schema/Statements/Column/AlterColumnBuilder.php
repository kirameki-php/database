<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Schema\SchemaHandler;

class AlterColumnBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param AlterColumnAction $action
     */
    public function __construct(
        protected readonly SchemaHandler $handler,
        protected readonly AlterColumnAction $action,
    )
    {
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function int(?int $size = null): ColumnBuilder
    {
        return new IntColumnBuilder($this->handler, $this->setType(__FUNCTION__, $size));
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function float(?int $size = null): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__, $size));
    }

    /**
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function decimal(?int $precision = null, ?int $scale = null): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__, $precision, $scale));
    }

    /**
     * @return ColumnBuilder
     */
    public function bool(): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__));
    }

    /**
     * @param int|null $precision
     * @return ColumnBuilder
     */
    public function datetime(?int $precision = null): ColumnBuilder
    {
        return new TimestampColumnBuilder($this->handler, $this->setType(__FUNCTION__, $precision));
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function string(?int $size = null): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__, $size));
    }

    /**
     * @return ColumnBuilder
     */
    public function text(): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__));
    }

    /**
     * @return ColumnBuilder
     */
    public function json(): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__));
    }

    /**
     * @return ColumnBuilder
     */
    public function binary(): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->setType(__FUNCTION__));
    }

    /**
     * @return ColumnBuilder
     */
    public function uuid(): ColumnBuilder
    {
        ;
        return new UuidColumnBuilder($this->handler, $this->setType(__FUNCTION__));
    }

    /**
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return ColumnDefinition
     */
    protected function setType(string $type, ?int $size = null, ?int $scale = null): ColumnDefinition
    {
        $definition = $this->action->definition;
        $definition->type = $type;
        $definition->size = $size;
        $definition->scale = $scale;
        return $definition;
    }
}
