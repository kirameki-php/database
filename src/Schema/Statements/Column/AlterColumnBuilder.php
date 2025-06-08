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
        $this->setType(__FUNCTION__, $size);
        return new IntColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function float(?int $size = null): ColumnBuilder
    {
        $this->setType(__FUNCTION__, $size);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function decimal(?int $precision = null, ?int $scale = null): ColumnBuilder
    {
        $this->setType(__FUNCTION__, $precision, $scale);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @return ColumnBuilder
     */
    public function bool(): ColumnBuilder
    {
        $this->setType(__FUNCTION__);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @param int|null $precision
     * @return ColumnBuilder
     */
    public function datetime(?int $precision = null): ColumnBuilder
    {
        $this->setType(__FUNCTION__, $precision);
        return new TimestampColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function string(?int $size = null): ColumnBuilder
    {
        $this->setType(__FUNCTION__, $size);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @return ColumnBuilder
     */
    public function text(): ColumnBuilder
    {
        $this->setType(__FUNCTION__);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @return ColumnBuilder
     */
    public function json(): ColumnBuilder
    {
        $this->setType(__FUNCTION__);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @return ColumnBuilder
     */
    public function binary(): ColumnBuilder
    {
        $this->setType(__FUNCTION__);
        return new ColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @return ColumnBuilder
     */
    public function uuid(): ColumnBuilder
    {
        $this->setType(__FUNCTION__);
        return new UuidColumnBuilder($this->handler, $this->action->definition);
    }

    /**
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return void
     */
    protected function setType(string $type, ?int $size = null, ?int $scale = null): void
    {
        $definition = $this->action->definition;
        $definition->type = $type;
        $definition->size = $size;
        $definition->scale = $scale;
    }
}
